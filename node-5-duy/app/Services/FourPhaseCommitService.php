<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FourPhaseCommitService
{
    private array $allNodes = [
        'Node 1 (Khánh)' => 'https://node-1-khanh.onrender.com',
        'Node 2 (Khải)'  => 'https://node-2-khai-80yz.onrender.com',
        'Node 3 (Ngọc)'  => 'https://node-3-ngocc.onrender.com',
        'Node 4 (Kiên)'  => 'https://node-kien.onrender.com',
        'Node 5 (Duy)'   => 'https://node-5-duy-b0ca.onrender.com',
    ];

    private string $myUrl   = '';
    private int    $timeout = 25;

    public function __construct()
    {
        $this->myUrl = rtrim(config('app.url'), '/');
    }

    public function executeTransaction(array $data): array
    {
        $transactionId = $data['id'];
        $roomId        = $data['room_id'];
        $customerName  = $data['name'] ?? '';

        Log::info("[4PC] ▶ START | txn={$transactionId} | room={$roomId} | coordinator={$this->myUrl}");
        $remoteNodes = $this->getOtherNodes();

        // PHA 1: TIẾP NHẬN
        if (!$this->localCanCommit($transactionId, $roomId)) {
            throw new \Exception("Phòng {$roomId} đang bị KHÓA tại Server Điều phối! Không thể tiếp tục.");
        }

        // PHA 2: PHÂN TÁN
        $canCommitResponses = $this->broadcastCanCommit($transactionId, $roomId, $remoteNodes);

        // PHA 3: PHẢN HỒI
        $yesNodes = []; $noNodes = []; $deadNodes = [];
        foreach ($canCommitResponses as $name => ['url' => $url, 'vote' => $vote, 'dead' => $dead]) {
            if ($vote === 'YES') { $yesNodes[$name] = $url; }
            else { $noNodes[] = $name; if ($dead) $deadNodes[] = $name; }
        }

        if (count($noNodes) > 0) {
            $this->broadcastAbortParallel($yesNodes, $transactionId, $roomId, $customerName, 'VOTED_NO_BY_PEER');
            $msg = count($deadNodes) > 0
                ? 'Server [' . implode(', ', $deadNodes) . '] không phản hồi (đang ngủ đông hoặc sập).'
                : 'Server [' . implode(', ', $noNodes) . '] từ chối vì phòng đã được đặt.';
            throw new \Exception("Giao dịch HỦY! {$msg} Vui lòng thử lại sau.");
        }

        // PHA 4: ĐỒNG BỘ & CHỐT HẠ
        $ackResponses = $this->broadcastPreCommit($transactionId, $roomId, $customerName, $yesNodes);
        $failedAck    = array_filter($ackResponses, fn($r) => $r !== 'ACK');
        if (count($failedAck) > 0) {
            $this->broadcastAbortParallel($yesNodes, $transactionId, $roomId, $customerName, 'PRE_COMMIT_FAILED');
            throw new \Exception('Pre-Commit thất bại! Hệ thống đã Rollback an toàn. Vui lòng thử lại.');
        }

        $this->localCommit($transactionId, $roomId, $customerName);
        $this->broadcastDoCommit($transactionId, $yesNodes);

        return [
            'status'          => 'success',
            'message'         => 'Đặt phòng thành công! Tất cả 5 server đã đồng bộ dữ liệu.',
            'transaction_id'  => $transactionId,
            'committed_nodes' => array_merge(['Coordinator'], array_keys($yesNodes)),
            'dead_nodes'      => [],
        ];
    }

    private function broadcastCanCommit(string $transactionId, string $roomId, array $remoteNodes): array
    {
        if (empty($remoteNodes)) return [];
        $results   = [];
        $responses = Http::pool(function ($pool) use ($remoteNodes, $transactionId, $roomId) {
            foreach ($remoteNodes as $name => $url) {
                $pool->as($name)->withoutVerifying()->timeout($this->timeout)
                    ->post($url . '/api/can-commit', ['id' => $transactionId, 'room_id' => $roomId]);
            }
        });
        foreach ($remoteNodes as $name => $url) {
            try {
                $res = $responses[$name];
                $results[$name] = ['url' => $url, 'vote' => ($res->ok() && $res->json('status') === 'YES') ? 'YES' : 'NO', 'dead' => false];
            } catch (\Exception $e) {
                $results[$name] = ['url' => $url, 'vote' => 'NO', 'dead' => true];
            }
        }
        return $results;
    }

    private function broadcastPreCommit(string $transactionId, string $roomId, string $customerName, array $yesNodes): array
    {
        if (empty($yesNodes)) return [];
        $results   = [];
        $responses = Http::pool(function ($pool) use ($yesNodes, $transactionId, $roomId, $customerName) {
            foreach ($yesNodes as $name => $url) {
                $pool->as($name)->withoutVerifying()->timeout($this->timeout)
                    ->post($url . '/api/pre-commit', ['transaction_id' => $transactionId, 'room_id' => $roomId, 'customer_name' => $customerName]);
            }
        });
        foreach ($yesNodes as $name => $url) {
            try { $res = $responses[$name]; $results[$name] = ($res->ok() && $res->json('status') === 'ACK') ? 'ACK' : 'FAILED'; }
            catch (\Exception $e) { $results[$name] = 'FAILED'; }
        }
        return $results;
    }

    private function broadcastDoCommit(string $transactionId, array $nodes): void
    {
        if (empty($nodes)) return;
        try {
            Http::pool(function ($pool) use ($nodes, $transactionId) {
                foreach ($nodes as $name => $url) {
                    $pool->as($name)->withoutVerifying()->timeout(15)->post($url . '/api/do-commit', ['transaction_id' => $transactionId]);
                }
            });
        } catch (\Exception $e) { Log::error("[4PC][DO-COMMIT] " . $e->getMessage()); }
    }

    private function broadcastAbortParallel(array $nodes, string $transactionId, string $roomId, string $customerName, string $reason): void
    {
        if (empty($nodes)) return;
        try {
            Http::pool(function ($pool) use ($nodes, $transactionId, $roomId, $customerName, $reason) {
                foreach ($nodes as $name => $url) {
                    $pool->as($name)->withoutVerifying()->timeout(8)
                        ->post($url . '/api/abort', ['transaction_id' => $transactionId, 'room_id' => $roomId, 'customer_name' => $customerName, 'reason' => $reason]);
                }
            });
        } catch (\Exception $e) { Log::error("[4PC][ABORT] " . $e->getMessage()); }
    }

    private function localCanCommit(string $transactionId, string $roomId): bool
    {
        return !DB::table('node_bookings')->where('room_id', $roomId)->where('transaction_id', '!=', $transactionId)
            ->where('status', 'pending')->where('created_at', '>=', now()->subMinutes(5))->exists();
    }

    private function localCommit(string $transactionId, string $roomId, string $customerName): void
    {
        DB::table('node_bookings')->updateOrInsert(
            ['transaction_id' => $transactionId, 'node_port' => $this->myUrl],
            ['room_id' => $roomId, 'customer_name' => $customerName, 'status' => 'committed', 'created_at' => now(), 'updated_at' => now()]
        );
    }

    private function getOtherNodes(): array
    {
        return array_filter($this->allNodes, fn($url) => rtrim($url, '/') !== $this->myUrl);
    }
}