<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class FourPhaseCommitService
{
    protected $nodes;

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

    public function executeTransaction(array $bookingData): array
    {
        $transactionId = $bookingData['id'];
        $roomId = $bookingData['room_id'] ?? null;
        $customerName = $bookingData['name'] ?? null;
        $pointOfNoReturn = false; 
        
        // Khởi tạo sổ nợ trống
        $deadNodesList = []; 

        try {
            if (!$this->sendQuorumRequests('/api/can-commit', ['id' => $transactionId, 'room_id' => $roomId], 'YES', $deadNodesList)) {
                $this->abortTransaction($transactionId, "TỪ CHỐI (PHA 1)", $roomId, $customerName);
                throw new \Exception("Giao dịch bị từ chối: Không đủ Node sống hoặc bị chiếm phòng!");
            }

            if (!$this->sendQuorumRequests('/api/pre-commit', ['transaction_id' => $transactionId, 'room_id' => $roomId, 'customer_name' => $customerName], 'ACK', $deadNodesList)) {
                $this->abortTransaction($transactionId, "TỪ CHỐI (PHA 2)", $roomId, $customerName);
                throw new \Exception("Giao dịch bị từ chối: Lỗi ở Pha chuẩn bị!");
            }

            $pointOfNoReturn = true;

            $this->sendQuorumRequests('/api/do-commit', ['transaction_id' => $transactionId], 'SUCCESS', $deadNodesList);

            return [
                'status' => 'success',
                'dead_nodes' => array_unique($deadNodesList)
            ];

        } catch (\Exception $e) {
            $errorMsg = $e->getMessage();
            
            if ($pointOfNoReturn) {
                return [
                    'status' => 'success',
                    'dead_nodes' => array_unique($deadNodesList)
                ];
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

    public function abortTransaction($transactionId, $reason = "ABORTED", $roomId = null, $customerName = null) {
        foreach ($this->nodes as $nodeUrl) {
            try {
                Http::withoutVerifying()->timeout(3)->post($nodeUrl . '/api/abort', [
                    'transaction_id' => $transactionId, 'reason' => $reason, 'room_id' => $roomId, 'customer_name' => $customerName
                ]);
            } catch (\Exception $e) {}
        }
    }

    // VŨ KHÍ TỐI THƯỢNG: TUẦN TỰ, TÓM CỔ TỪNG MÁY KHÔNG BỎ SÓT (BỎ HTTP::POOL)
    private function sendQuorumRequests($endpoint, $payload, $expectedStatus, &$deadNodesList): bool
    {
        $successCount = 0;
        $roomHijackedError = null;

        $nodeNames = [
            'https://luxury-hotel-distributed-system-49mq.onrender.com' => 'Máy Đầu Não',
            'https://node-3-ngocc.onrender.com' => 'Máy Ngọc',
            'https://node-2-khai-80yz.onrender.com' => 'Máy Khải',
            'https://node-kien.onrender.com' => 'Máy Kiên',
            'https://node-5-duy-b0ca.onrender.com' => 'Máy Duy'
        ];

        foreach ($this->nodes as $nodeUrl) {
            try {
                // Đi từng nhà gõ cửa, nếu 3s không thưa -> Đạp cửa ghi sổ!
                $response = Http::withoutVerifying()->timeout(3)->post($nodeUrl . $endpoint, $payload);

                // KIỂM TRA CỰC GẮT: Máy chết, hoặc Render trả về trang HTML Suspend (json() = null)
                if (!$response->ok() || $response->json('status') === null) {
                    $deadNodesList[] = $nodeNames[$nodeUrl] ?? "Máy ẩn danh";
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
                    $deadNodesList[] = $nodeNames[$nodeUrl] ?? "Máy ẩn danh";
                }

            } catch (\Exception $e) {
                // Bắt trọn ổ: Lỗi sập server, đứt cáp quang, nghẽn mạng...
                $deadNodesList[] = $nodeNames[$nodeUrl] ?? "Máy ẩn danh";
            }
        }

        if ($roomHijackedError) {
            throw new \Exception($roomHijackedError);
        }

        return $successCount >= 1; // Chỉ cần 1 máy sống là tiếp tục!
    }
}