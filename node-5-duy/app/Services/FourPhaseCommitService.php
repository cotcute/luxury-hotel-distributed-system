<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class FourPhaseCommitService
{
    protected $allNodes;

    public function __construct()
    {
        $this->allNodes = [
            'https://node-1-khanh.onrender.com' => 'Máy 1 (Khánh)',
            'https://node-2-khai-80yz.onrender.com' => 'Máy 2 (Khải)',
            'https://node-3-ngocc.onrender.com' => 'Máy 3 (Ngọc)',
            'https://node-kien.onrender.com' => 'Máy 4 (Kiên)',
            'https://node-5-duy-b0ca.onrender.com' => 'Máy 5 (Duy)'
        ];
    }

    public function executeTransaction(array $bookingData): array
    {
        $transactionId = $bookingData['id'];
        $roomId = $bookingData['room_id'];
        $customerName = $bookingData['name'];
        $deadNodesList = []; 

        try {
            // PHA 1: PRE-CHECK (A hỏi B, C, D, E: "OK không?")
            if (!$this->sendQuorumRequests('/api/can-commit', ['id' => $transactionId, 'room_id' => $roomId], 'YES', $deadNodesList)) {
                $this->abortTransaction($transactionId, "ABORTED", $roomId, $customerName);
                throw new \Exception("Phòng đã bị chiếm trên Server khác!");
            }

            // PHA 2: RESERVE (Tất cả Server giữ tài nguyên - Pending)
            if (!$this->sendQuorumRequests('/api/pre-commit', ['transaction_id' => $transactionId, 'room_id' => $roomId, 'customer_name' => $customerName], 'ACK', $deadNodesList)) {
                $this->abortTransaction($transactionId, "ABORTED", $roomId, $customerName);
                throw new \Exception("Lỗi ở Pha 2: Reserve.");
            }

            // PHA 3: CONFIRM (A quyết định dựa vào vote - Logic nằm trong hàm sendQuorumRequests)
            
            // PHA 4: FINALIZE (Các server Commit + Đồng bộ)
            $this->sendQuorumRequests('/api/do-commit', ['transaction_id' => $transactionId], 'SUCCESS', $deadNodesList);

            return [
                'status' => 'success',
                'dead_nodes' => array_unique($deadNodesList)
            ];

        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function abortTransaction($transactionId, $reason, $roomId, $customerName) {
        foreach ($this->allNodes as $nodeUrl => $nodeName) {
            try {
                Http::withoutVerifying()->timeout(3)->post($nodeUrl . '/api/abort', [
                    'transaction_id' => $transactionId, 'reason' => $reason, 'room_id' => $roomId, 'customer_name' => $customerName
                ]);
            } catch (\Exception $e) {}
        }
    }

    private function sendQuorumRequests($endpoint, $payload, $expectedStatus, &$deadNodesList): bool
    {
        $aliveCount = 0;
        $agreedCount = 0;
        $hijackedError = null;

        foreach ($this->allNodes as $nodeUrl => $nodeName) {
            try {
                $response = Http::withoutVerifying()->timeout(3)->post($nodeUrl . $endpoint, $payload);

                if (!$response->ok() || $response->json('status') === null) {
                    $deadNodesList[] = $nodeName;
                    continue; 
                }

                $aliveCount++;
                $status = $response->json('status');

                if (strpos($endpoint, 'can-commit') !== false && $status === 'NO') {
                    $hijackedError = true;
                    break; 
                }

                if ($status === $expectedStatus) {
                    $agreedCount++;
                } else {
                    $deadNodesList[] = $nodeName;
                }
            } catch (\Exception $e) {
                $deadNodesList[] = $nodeName;
            }
        }

        if ($hijackedError) return false;

        // Bỏ qua máy chết. Các máy SỐNG đều phải đồng thuận!
        return ($aliveCount > 0 && $agreedCount === $aliveCount);
    }
}