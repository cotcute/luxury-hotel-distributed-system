<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Pool;

class FourPhaseCommitService
{
    protected $nodes;

    public function __construct()
    {
        $this->nodes = [
            'https://luxury-hotel-distributed-system-49mq.onrender.com', // Đầu não
            'https://node-3-ngocc.onrender.com',
            'https://node-2-khai-80yz.onrender.com',
            'https://node-kien.onrender.com',
            'https://node-5-duy-b0ca.onrender.com'
        ];
    }

    public function executeTransaction(array $bookingData): array
    {
        $transactionId = $bookingData['id'];
        $roomId = $bookingData['room_id'] ?? null;
        $customerName = $bookingData['name'] ?? null;
        $pointOfNoReturn = false; 
        
        // VŨ KHÍ MỚI: Khai báo biến ngay trong hàm, truyền tay qua các Pha, KHÔNG BAO GIỜ bị mất dữ liệu!
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

            // TRẢ VỀ TRỰC TIẾP KẾT QUẢ VÀ DANH SÁCH MÁY CHẾT
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

    // GHI SỔ NỢ BẰNG THAM CHIẾU (&$deadNodesList)
    private function sendQuorumRequests($endpoint, $payload, $expectedStatus, &$deadNodesList): bool
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
            // Đã kiểm tra cực gắt: Request chết, timeout, Render trả về HTML 502, JSON lỗi... tóm cổ hết!
            if (!$response || $response instanceof \Exception || !$response->ok() || $response->json('status') === null) {
                $deadNodesList[] = $nodeNames[$nodeUrl] ?? 'Máy Ẩn Danh';
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
                $deadNodesList[] = $nodeNames[$nodeUrl] ?? 'Máy Ẩn Danh';
            }
        }

        if ($roomHijackedError) { throw new \Exception($roomHijackedError); }
        
        return $successCount >= 1; 
    }
}   