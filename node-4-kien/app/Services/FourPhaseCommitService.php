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
            // 🔹 PHA 1: PRE-CHECK
            if (!$this->sendQuorumRequests('/api/can-commit', [
                'id' => $transactionId,
                'room_id' => $roomId
            ], 'YES', $deadNodesList)) {

                $this->abortTransaction($transactionId, "ABORTED", $roomId, $customerName);
                throw new \Exception("Phòng đã bị chiếm trên Server khác!");
            }

            // 🔹 PHA 2: PRE-COMMIT (RESERVE)
            if (!$this->sendQuorumRequests('/api/pre-commit', [
                'transaction_id' => $transactionId,
                'room_id' => $roomId,
                'customer_name' => $customerName
            ], 'ACK', $deadNodesList)) {

                $this->abortTransaction($transactionId, "ABORTED", $roomId, $customerName);
                throw new \Exception("Lỗi ở Pha 2: Reserve.");
            }

            // 🔹 PHA 3: (LOGIC QUORUM nằm trong sendQuorumRequests)

            // 🔹 PHA 4: COMMIT
            $this->sendQuorumRequests('/api/do-commit', [
                'transaction_id' => $transactionId
            ], 'SUCCESS', $deadNodesList);

            return [
                'status' => 'success',
                'dead_nodes' => array_unique($deadNodesList)
            ];

        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function abortTransaction($transactionId, $reason, $roomId, $customerName)
    {
        foreach ($this->allNodes as $nodeUrl => $nodeName) {
            try {
                Http::withoutVerifying()->timeout(3)->post($nodeUrl . '/api/abort', [
                    'transaction_id' => $transactionId,
                    'reason' => $reason,
                    'room_id' => $roomId,
                    'customer_name' => $customerName
                ]);
            } catch (\Exception $e) {
                // bỏ qua lỗi
            }
        }
    }

    // 🚀 QUORUM VERSION (>= 3/5 là OK)
    private function sendQuorumRequests($endpoint, $payload, $expectedStatus, &$deadNodesList): bool
    {
        $agreedCount = 0;
        $hijackedError = false;

        $totalNodes = count($this->allNodes); // 5 máy
        $quorumRequired = ceil($totalNodes / 2); // 3 máy

        // ⚡ GỬI SONG SONG
        $responses = Http::pool(function (\Illuminate\Http\Client\Pool $pool) use ($endpoint, $payload) {
            foreach ($this->allNodes as $nodeUrl => $nodeName) {
                $pool->as($nodeUrl)
                    ->withoutVerifying()
                    ->timeout(5)
                    ->post($nodeUrl . $endpoint, $payload);
            }
        });

        foreach ($responses as $nodeUrl => $response) {
            $nodeName = $this->allNodes[$nodeUrl];

            // ❌ NODE CHẾT / TIMEOUT / HTML
            if ($response instanceof \Exception || !$response->ok() || $response->json('status') === null) {
                $deadNodesList[] = $nodeName;
                continue;
            }

            $status = $response->json('status');

            // ❗ BỊ CƯỚP PHÒNG
            if (strpos($endpoint, 'can-commit') !== false && $status === 'NO') {
                $hijackedError = true;
                continue;
            }

            // ✅ ĐỒNG Ý
            if ($status === $expectedStatus) {
                $agreedCount++;
            } else {
                $deadNodesList[] = $nodeName;
            }
        }

        // ❗ Nếu có máy báo NO → fail ngay
        if ($hijackedError) {
            return false;
        }

        // 🔥 QUORUM LOGIC
        return ($agreedCount >= $quorumRequired);
    }
}   