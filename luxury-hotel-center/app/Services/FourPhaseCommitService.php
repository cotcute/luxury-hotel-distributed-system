<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Log;

class FourPhaseCommitService
{
    protected $nodes;

    public function __construct()
    {
        // ĐIỀN ĐÚNG 5 LINK RENDER CỦA KHÁNH, KHẢI, NGỌC, KIÊN, DUY VÀO ĐÂY
        $this->nodes = [
            'https://node-1-khanh.onrender.com',
            'https://node-2-khai.onrender.com',
            'https://node-3-khaiii.onrender.com', 
            'https://node-4-kien.onrender.com',
            'https://node-5-duy.onrender.com'
        ];
    }

    public function executeTransaction(array $bookingData): bool
    {
        if (count($this->nodes) !== 5) {
            throw new \Exception("Hệ thống chưa đủ cấu hình 5 Server Node!");
        }

        $transactionId = $bookingData['id'];
        $roomId = $bookingData['room_id'] ?? null;
        $customerName = $bookingData['name'] ?? null;
        $pointOfNoReturn = false; 

        try {
            // PHA 1: HỎI Ý KIẾN
            if (!$this->phase1CanCommit($bookingData)) {
                $this->abortTransaction($transactionId, "TỪ CHỐI (PHA 1)", $roomId, $customerName);
                throw new \Exception("Giao dịch bị từ chối: Không đủ số lượng Node đồng ý, hoặc mạng quá yếu!");
            }

            // PHA 2: CHUẨN BỊ
            if (!$this->phase2PreCommit($transactionId, $roomId, $customerName)) {
                $this->abortTransaction($transactionId, "TỪ CHỐI (PHA 2)", $roomId, $customerName);
                throw new \Exception("Giao dịch bị từ chối: Lỗi ở Pha chuẩn bị!");
            }

            $pointOfNoReturn = true;
            sleep(10); 

            // PHA 3: CHỐT HẠ
            $this->phase3DoCommit($transactionId);
            return true; 

        } catch (\Exception $e) {
            $errorMsg = $e->getMessage();
            
            if ($pointOfNoReturn) {
                Log::warning("Giao dịch $transactionId thành công. Nhưng có lỗi cục bộ lúc Do-Commit: " . $errorMsg);
                return true; 
            }

            // Xử lý báo lỗi ra màn hình
            if (strpos($errorMsg, 'CƯỚP PHÒNG') !== false) {
                $this->abortTransaction($transactionId, "ABORTED (BỊ CƯỚP PHÒNG)", $roomId, $customerName);
                throw $e; 
            } 
            else {
                $this->abortTransaction($transactionId, "HỦY (KHÔNG ĐỦ NODE SỐNG)", $roomId, $customerName);
                throw $e;
            }
        }
    }

    private function phase1CanCommit($data): bool {
        return $this->sendQuorumRequests('/api/can-commit', ['id' => $data['id'], 'room_id' => $data['room_id']], 'YES');
    }

    private function phase2PreCommit($transactionId, $roomId = null, $customerName = null): bool {
        return $this->sendQuorumRequests('/api/pre-commit', ['transaction_id' => $transactionId, 'room_id' => $roomId, 'customer_name' => $customerName], 'ACK');
    }

    private function phase3DoCommit($transactionId): bool {
        return $this->sendQuorumRequests('/api/do-commit', ['transaction_id' => $transactionId], 'SUCCESS');
    }

    public function abortTransaction($transactionId, $reason = "ABORTED", $roomId = null, $customerName = null) {
        foreach ($this->nodes as $nodeUrl) {
            try {
                Http::withoutVerifying()->timeout(3)->post($nodeUrl . '/api/abort', [
                    'transaction_id' => $transactionId,
                    'reason' => $reason,
                    'room_id' => $roomId,
                    'customer_name' => $customerName
                ]);
            } catch (\Exception $e) {}
        }
    }

    // =========================================================================
    // VŨ KHÍ TỐI THƯỢNG MỚI: CƠ CHẾ QUORUM (Tolerate 1 Dead Node)
    // =========================================================================
    private function sendQuorumRequests($endpoint, $payload, $expectedStatus): bool
    {
        // Gửi lệnh song song cho cả 5 máy cùng lúc
        $responses = Http::pool(function (Pool $pool) use ($endpoint, $payload) {
            foreach ($this->nodes as $nodeUrl) {
                // Chờ tối đa 5 giây. Nếu thằng nào tắt máy, mặc kệ nó cho chết luôn.
                $pool->as($nodeUrl)->withOptions(['verify' => false])->connectTimeout(3)->timeout(5)->post($nodeUrl . $endpoint, $payload);
            }
        });

        $successCount = 0;
        $roomHijackedError = null; 

        foreach ($responses as $nodeUrl => $response) {
            // Nếu thằng nào quăng lỗi mạng (tắt máy), ta cứ lờ đi (kệ nó)
            if ($response instanceof \Exception || !$response->ok()) {
                continue; 
            }
            
            $status = $response->json('status');
            
            // ƯU TIÊN 1: Lỗi Cướp Phòng (Logic nghiệp vụ) -> Phải bắt liền!
            if (strpos($endpoint, 'can-commit') !== false && $status === 'NO') {
                $roomHijackedError = "CƯỚP PHÒNG|Phòng số {$payload['room_id']} vừa bị khách khác khóa trước!";
                continue; 
            }

            // Nếu nó phản hồi đúng (YES / ACK / SUCCESS) thì cộng 1 điểm
            if ($status === $expectedStatus) {
                $successCount++;
            }
        }

        // TÒA TUYÊN ÁN:
        // 1. Nếu có thằng báo bị cướp phòng -> Hủy toàn bộ (Bảo vệ dữ liệu)
        if ($roomHijackedError) {
            throw new \Exception($roomHijackedError);
        }

        // 2. CƠ CHẾ KHOAN DUNG LỖI (FAULT TOLERANCE)
        // Hệ thống có 5 máy. Ta cho phép chết 1 máy. Vậy cần tối thiểu 4 máy báo Thành công.
        $minimumRequired = count($this->nodes) - 1; 

        if ($successCount >= $minimumRequired) {
            return true; // Dù 1 thằng có tắt, 4 thằng OK thì ta VẪN DUYỆT!
        }

        // 3. Nếu số máy sống < 4 (VD: chết 2 máy trở lên) -> Văng lỗi sập hệ thống
        throw new \Exception("Lỗi Mạng: Hệ thống cần ít nhất $minimumRequired máy hoạt động, nhưng hiện tại chỉ có $successCount máy phản hồi!");
    }
}