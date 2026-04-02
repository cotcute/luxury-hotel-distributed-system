<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Log;

class FourPhaseCommitService
{
    protected $nodes;
    protected $deadNodesList = []; // Cuốn sổ ghi nợ các máy bị tắt

    public function __construct()
    {
        $this->nodes = [
            'https://luxury-hotel-distributed-system-49mq.onrender.com', // Đầu não
            'https://node-3-ngocc.onrender.com', // Ngọc
            'https://node-2-khai-80yz.onrender.com', // Khải
            'https://node-kien.onrender.com', // Kiên
            'https://node-5-duy-b0ca.onrender.com' // Duy
        ];
    }

    public function getDeadNodes()
    {
        return array_unique($this->deadNodesList); 
    }

    public function executeTransaction(array $bookingData): bool
    {
        $this->deadNodesList = []; // Reset sổ nợ mỗi khi có khách mới

        $transactionId = $bookingData['id'];
        $roomId = $bookingData['room_id'] ?? null;
        $customerName = $bookingData['name'] ?? null;
        $pointOfNoReturn = false; 

        try {
            if (!$this->phase1CanCommit($bookingData)) {
                $this->abortTransaction($transactionId, "TỪ CHỐI (PHA 1)", $roomId, $customerName);
                throw new \Exception("Giao dịch bị từ chối: Không đủ Node sống hoặc bị chiếm phòng!");
            }

            if (!$this->phase2PreCommit($transactionId, $roomId, $customerName)) {
                $this->abortTransaction($transactionId, "TỪ CHỐI (PHA 2)", $roomId, $customerName);
                throw new \Exception("Giao dịch bị từ chối: Lỗi ở Pha chuẩn bị!");
            }

            $pointOfNoReturn = true;

            $this->phase3DoCommit($transactionId);
            return true; 

        } catch (\Exception $e) {
            $errorMsg = $e->getMessage();
            
            if ($pointOfNoReturn) {
                return true; 
            }

            if (strpos($errorMsg, 'CƯỚP PHÒNG') !== false) {
                $this->abortTransaction($transactionId, "ABORTED (BỊ CƯỚP PHÒNG)", $roomId, $customerName);
                throw $e; 
            } else {
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

    private function sendQuorumRequests($endpoint, $payload, $expectedStatus): bool
    {
        $nodeNames = [
            'https://luxury-hotel-distributed-system-49mq.onrender.com' => 'Máy Đầu Não',
            'https://node-3-ngocc.onrender.com' => 'Máy Ngọc',
            'https://node-2-khai-80yz.onrender.com' => 'Máy Khải',
            'https://node-kien.onrender.com' => 'Máy Kiên',
            'https://node-5-duy-b0ca.onrender.com' => 'Máy Duy'
        ];

        $responses = Http::pool(function (Pool $pool) use ($endpoint, $payload) {
            foreach ($this->nodes as $nodeUrl) {
                $pool->as($nodeUrl)->withOptions(['verify' => false])->connectTimeout(3)->timeout(5)->post($nodeUrl . $endpoint, $payload);
            }
        });

        $successCount = 0;
        $roomHijackedError = null; 

        foreach ($responses as $nodeUrl => $response) {
            // VŨ KHÍ TỐI THƯỢNG FIX LỖI: Bắt cả lỗi mạng VÀ lỗi Render trả về trang HTML 200 OK (lúc này json('status') sẽ bị null)
            if ($response instanceof \Exception || !$response->ok() || $response->json('status') === null) {
                $this->deadNodesList[] = $nodeNames[$nodeUrl] ?? 'Máy Ẩn Danh';
                continue; 
            }
            
            $status = $response->json('status');
            
            if (strpos($endpoint, 'can-commit') !== false && $status === 'NO') {
                $roomHijackedError = "CƯỚP PHÒNG|Phòng số {$payload['room_id']} vừa bị khách khác khóa trước!";
                continue; 
            }

            if ($status === $expectedStatus) {
                $successCount++;
            } else {
                // Nếu trả về JSON nhưng không phải trạng thái thành công -> Cũng coi như máy đó bị lỗi
                $this->deadNodesList[] = $nodeNames[$nodeUrl] ?? 'Máy Ẩn Danh';
            }
        }

        if ($roomHijackedError) {
            throw new \Exception($roomHijackedError);
        }

        $minimumRequired = 1; 

        if ($successCount >= $minimumRequired) {
            return true; 
        }

        throw new \Exception("Lỗi Mạng Nghiêm Trọng: Sập toàn bộ hệ thống! Không có máy nào phản hồi.");
    }
}