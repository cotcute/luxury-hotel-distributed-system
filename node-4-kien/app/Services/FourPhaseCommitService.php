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
    private int $quorum  = 3;
    private int $timeout = 30;
    private string $myUrl = '';

    public function __construct() { $this->myUrl = rtrim(config('app.url'), '/'); }

    public function executeTransaction(array $data): array
    {
        $txnId = $data['id']; $roomId = $data['room_id']; $customerName = $data['name'] ?? '';
        $others = $this->getOtherNodes();
        if (!$this->localCanCommit($txnId, $roomId))
            throw new \Exception("Phòng {$roomId} đang bị khóa tại Server điều phối. Vui lòng thử phòng khác.");
        $votes = $this->broadcastCanCommit($txnId, $roomId, $others);
        $yesNodes = []; $noNodes = []; $sleepingNodes = [];
        foreach ($votes as $name => ['url' => $url, 'vote' => $vote]) {
            if ($vote === 'YES') $yesNodes[$name] = $url;
            elseif ($vote === 'NO') $noNodes[] = $name;
            else $sleepingNodes[] = $name;
        }
        if (count($noNodes) > 0) {
            $this->broadcastAbort($yesNodes, $txnId, $roomId, $customerName, 'ROOM_LOCKED');
            throw new \Exception("[" . implode(', ', $noNodes) . "] báo phòng {$roomId} đã bị đặt! Giao dịch hủy.");
        }
        $totalYes = 1 + count($yesNodes);
        if ($totalYes < $this->quorum) {
            $this->broadcastAbort($yesNodes, $txnId, $roomId, $customerName, 'NO_QUORUM');
            throw new \Exception("Không đủ quorum ({$totalYes}/" . (1+count($others)) . " nodes). Server đang ngủ: [" . implode(', ', $sleepingNodes) . "]. Thử lại sau 30s.");
        }
        $ackNodes = $this->broadcastPreCommit($txnId, $roomId, $customerName, $yesNodes);
        $this->localCommit($txnId, $roomId, $customerName);
        $this->broadcastDoCommit($txnId, $ackNodes);
        return ['status' => 'success', 'message' => 'Đặt phòng thành công!', 'dead_nodes' => $sleepingNodes];
    }

    private function broadcastCanCommit(string $txnId, string $roomId, array $nodes): array
    {
        if (empty($nodes)) return [];
        $responses = Http::pool(fn($pool) => array_map(fn($n, $url) => $pool->as($n)->withoutVerifying()->timeout($this->timeout)->post($url . '/api/can-commit', ['id' => $txnId, 'room_id' => $roomId]), array_keys($nodes), array_values($nodes)));
        $results = [];
        foreach ($nodes as $name => $url) {
            try {
                $res = $responses[$name]; $ct = $res->header('Content-Type') ?? '';
                if (str_contains($ct, 'text/html')) $results[$name] = ['url' => $url, 'vote' => 'SLEEPING'];
                elseif ($res->ok() && $res->json('status') === 'YES') $results[$name] = ['url' => $url, 'vote' => 'YES'];
                else $results[$name] = ['url' => $url, 'vote' => 'NO'];
            } catch (\Exception $e) { $results[$name] = ['url' => $url, 'vote' => 'SLEEPING']; }
        }
        return $results;
    }

    private function broadcastPreCommit(string $txnId, string $roomId, string $cname, array $yesNodes): array
    {
        if (empty($yesNodes)) return [];
        $acked = [];
        $responses = Http::pool(fn($pool) => array_map(fn($n, $url) => $pool->as($n)->withoutVerifying()->timeout($this->timeout)->post($url . '/api/pre-commit', ['transaction_id' => $txnId, 'room_id' => $roomId, 'customer_name' => $cname]), array_keys($yesNodes), array_values($yesNodes)));
        foreach ($yesNodes as $n => $url) { try { if ($responses[$n]->ok() && $responses[$n]->json('status') === 'ACK') $acked[$n] = $url; } catch (\Exception $e) {} }
        return $acked;
    }

    private function broadcastDoCommit(string $txnId, array $nodes): void
    {
        if (empty($nodes)) return;
        try { Http::pool(fn($pool) => array_map(fn($n, $url) => $pool->as($n)->withoutVerifying()->timeout(15)->post($url . '/api/do-commit', ['transaction_id' => $txnId]), array_keys($nodes), array_values($nodes))); } catch (\Exception $e) {}
    }

    private function broadcastAbort(array $nodes, string $txnId, string $roomId, string $customer, string $reason): void
    {
        if (empty($nodes)) return;
        try { Http::pool(fn($pool) => array_map(fn($n, $url) => $pool->as($n)->withoutVerifying()->timeout(8)->post($url . '/api/abort', ['transaction_id' => $txnId, 'room_id' => $roomId, 'customer_name' => $customer, 'reason' => $reason]), array_keys($nodes), array_values($nodes))); } catch (\Exception $e) {}
    }

    private function localCanCommit(string $txnId, string $roomId): bool
    {
        return !DB::table('node_bookings')->where('room_id', $roomId)->where('transaction_id', '!=', $txnId)->where('status', 'pending')->where('created_at', '>=', now()->subMinutes(5))->exists();
    }

    private function localCommit(string $txnId, string $roomId, string $customerName): void
    {
        DB::table('node_bookings')->updateOrInsert(['transaction_id' => $txnId, 'node_port' => $this->myUrl], ['room_id' => $roomId, 'customer_name' => $customerName, 'status' => 'committed', 'created_at' => now(), 'updated_at' => now()]);
    }

    private function getOtherNodes(): array { return array_filter($this->allNodes, fn($url) => rtrim($url, '/') !== $this->myUrl); }
}