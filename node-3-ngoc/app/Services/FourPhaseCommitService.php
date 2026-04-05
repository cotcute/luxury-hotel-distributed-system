<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FourPhaseCommitService
{
    private array $allNodes = [
        'Node 1 (Khánh)' => 'https://luxury-hotel-distributed-system-49mq.onrender.com',
        'Node 2 (Khải)'  => 'https://node-2-khai-80yz.onrender.com',
        'Node 3 (Ngọc)'  => 'https://node-3-ngocc.onrender.com',
        'Node 4 (Kiên)'  => 'https://node-kien.onrender.com',
        'Node 5 (Duy)'   => 'https://node-5-duy-b0ca.onrender.com',
    ];
    private int $quorum  = 1;
    private int $timeout = 30;
    private string $myUrl = '';

    public function __construct()
    {
        $this->myUrl = request()->getHost();
    }

    public function executeTransaction(array $data): array
    {
        $txnId        = $data['id'];
        $roomId       = $data['room_id'];
        $customerName = $data['name'] ?? '';

        // Bắt đầu giao dịch
        $this->logSystem($txnId, 'BẮT ĐẦU 4PC', "Nhạc trưởng [{$this->myUrl}] khởi động giao dịch #{$txnId} cho Phòng {$roomId}, khách '{$customerName}'", 'warning');

        $others = $this->getOtherNodes();

        // PHA 1: Kiểm tra local
        if (!$this->localCanCommit($txnId, $roomId)) {
            $this->logSystem($txnId, 'PHA 1: TỪ CHỐI', "Coordinator kiểm tra DB cục bộ → Phòng {$roomId} đang BỊ KHÓA!", 'error');
            throw new \Exception("Phòng {$roomId} đang bị khóa tại Server điều phối. Vui lòng thử phòng khác.");
        }
        $this->logSystem($txnId, 'PHA 1: KIỂM TRA LOCAL', "Coordinator kiểm tra DB cục bộ → OK, Phòng {$roomId} chưa bị khóa", 'success');

        // PHA 2: Broadcast CAN-COMMIT
        $nodeNames = implode(', ', array_keys($others));
        $this->logSystem($txnId, 'PHA 2: PHÂN TÁN', "Gửi CAN-COMMIT song song tới " . count($others) . " node: [{$nodeNames}]", 'info');

        $votes = $this->broadcastCanCommit($txnId, $roomId, $others);

        // Phân loại phiếu
        $yesNodes      = [];
        $noNodes       = [];
        $sleepingNodes = [];

        foreach ($votes as $name => ['url' => $url, 'vote' => $vote]) {
            if ($vote === 'YES')    { $yesNodes[$name] = $url; }
            elseif ($vote === 'NO') { $yesNodes[$name] = $url; $noNodes[] = $name; }
            else                    { $yesNodes[$name] = $url; $sleepingNodes[] = $name; }
        }

        // Tổng hợp kết quả phiếu bầu
        $voteDetails = [];
        foreach ($votes as $name => ['vote' => $vote]) {
            $icon = $vote === 'YES' ? '✅' : ($vote === 'NO' ? '⛔' : '💤');
            $voteDetails[] = "{$icon} {$name}: {$vote}";
        }
        $onlineCount = count($others) - count($sleepingNodes);
        $this->logSystem(
            $txnId,
            'PHA 2: KẾT QUẢ PHIẾU',
            implode(' | ', $voteDetails) . " | Online: {$onlineCount}/" . count($others) . " | Sleeping: [" . implode(', ', $sleepingNodes) . "]",
            empty($noNodes) ? 'success' : 'warning'
        );

        if (count($noNodes) > 0) {
            $this->logSystem($txnId, 'CHẾ ĐỘ ĐỘC TÀI', "Buộc các node báo NO phải tuân lệnh: [" . implode(', ', $noNodes) . "]", 'warning');
            Log::warning("[4PC] Bỏ qua các node báo NO: " . implode(', ', $noNodes));
        }

        $totalYes = 1 + count($yesNodes);
        if ($totalYes < $this->quorum) {
            $this->logSystem($txnId, 'THIẾU QUORUM', "Không đủ quorum ({$totalYes}/" . (1 + count($others)) . "). ABORT!", 'error');
            $this->broadcastAbort($yesNodes, $txnId, $roomId, $customerName, 'NO_QUORUM');
            throw new \Exception("Không đủ quorum ({$totalYes}/" . (1 + count($others)) . " nodes). Server đang ngủ: [" . implode(', ', $sleepingNodes) . "]. Thử lại sau 30s.");
        }

        // PHA 3: Pre-Commit
        $this->logSystem($txnId, 'PHA 3: KHÓA TOÀN MẠNG', "Đạt Quorum! Ra lệnh PRE-COMMIT tới " . count($yesNodes) . " node online: [" . implode(', ', array_keys($yesNodes)) . "]", 'info');
        $this->broadcastPreCommit($txnId, $roomId, $customerName, $yesNodes);

        // PHA 4: Commit local
        $this->logSystem($txnId, 'PHA 4: GHI LOCAL', "Coordinator [{$this->myUrl}] tự ghi vĩnh viễn vào CSDL của mình", 'success');
        $this->localCommit($txnId, $roomId, $customerName);

        // PHA 4: Broadcast Do-Commit
        $this->logSystem($txnId, 'PHA 4: ĐỒNG BỘ TOÀN MẠNG', "Broadcast DO-COMMIT tới " . count($yesNodes) . " node: [" . implode(', ', array_keys($yesNodes)) . "] — Đồng bộ hoàn tất!", 'success');
        $this->broadcastDoCommit($txnId, $yesNodes, $roomId, $customerName);

        $this->logSystem($txnId, 'HOÀN TẤT', "✅ Giao dịch #{$txnId} thành công! Phòng {$roomId} đặt cho '{$customerName}'. Sleeping: [" . implode(', ', $sleepingNodes) . "]", 'success');

        return ['status' => 'success', 'message' => 'Đặt phòng thành công!', 'dead_nodes' => $sleepingNodes];
    }

    private function broadcastCanCommit(string $txnId, string $roomId, array $nodes): array
    {
        if (empty($nodes)) return [];
        $responses = Http::pool(fn($pool) => array_map(
            fn($n, $url) => $pool->as($n)->withoutVerifying()->timeout($this->timeout)->post($url . '/api/can-commit', ['id' => $txnId, 'room_id' => $roomId]),
            array_keys($nodes), array_values($nodes)
        ));
        $results = [];
        foreach ($nodes as $name => $url) {
            try {
                $res = $responses[$name];
                if ($res instanceof \Exception) throw $res;
                $ct = $res->header('Content-Type') ?? '';
                if (str_contains($ct, 'text/html'))           $results[$name] = ['url' => $url, 'vote' => 'SLEEPING'];
                elseif ($res->ok() && $res->json('status') === 'YES') $results[$name] = ['url' => $url, 'vote' => 'YES'];
                else                                           $results[$name] = ['url' => $url, 'vote' => 'NO'];
            } catch (\Exception $e) {
                $results[$name] = ['url' => $url, 'vote' => 'SLEEPING'];
            }
        }
        return $results;
    }

    private function broadcastPreCommit(string $txnId, string $roomId, string $cname, array $yesNodes): array
    {
        if (empty($yesNodes)) return [];
        $acked = [];
        $responses = Http::pool(fn($pool) => array_map(
            fn($n, $url) => $pool->as($n)->withoutVerifying()->timeout($this->timeout)->post($url . '/api/pre-commit', ['transaction_id' => $txnId, 'room_id' => $roomId, 'customer_name' => $cname]),
            array_keys($yesNodes), array_values($yesNodes)
        ));
        foreach ($yesNodes as $n => $url) {
            try {
                $res = $responses[$n];
                if ($res instanceof \Exception) throw $res;
                if ($res->ok() && $res->json('status') === 'ACK') $acked[$n] = $url;
            } catch (\Exception $e) {}
        }
        return $acked;
    }

    private function broadcastDoCommit(string $txnId, array $nodes, string $roomId, string $cname): void
    {
        if (empty($nodes)) return;
        try {
            Http::pool(fn($pool) => array_map(
                fn($n, $url) => $pool->as($n)->withoutVerifying()->timeout(15)->post($url . '/api/do-commit', [
                    'transaction_id' => $txnId,
                    'room_id'        => $roomId,
                    'customer_name'  => $cname,
                ]),
                array_keys($nodes), array_values($nodes)
            ));
        } catch (\Exception $e) { Log::error("[4PC][COMMIT] " . $e->getMessage()); }
    }

    private function broadcastAbort(array $nodes, string $txnId, string $roomId, string $customer, string $reason): void
    {
        if (empty($nodes)) return;
        try {
            Http::pool(fn($pool) => array_map(
                fn($n, $url) => $pool->as($n)->withoutVerifying()->timeout(8)->post($url . '/api/abort', ['transaction_id' => $txnId, 'room_id' => $roomId, 'customer_name' => $customer, 'reason' => $reason]),
                array_keys($nodes), array_values($nodes)
            ));
        } catch (\Exception $e) {}
    }

    private function localCanCommit(string $txnId, string $roomId): bool
    {
        return !DB::table('node_bookings')
            ->where('room_id', $roomId)
            ->where('transaction_id', '!=', $txnId)
            ->where('status', 'pending')
            ->where('created_at', '>=', now()->subMinutes(5))
            ->exists();
    }

    private function localCommit(string $txnId, string $roomId, string $customerName): void
    {
        DB::table('node_bookings')->updateOrInsert(
            ['transaction_id' => $txnId, 'node_port' => $this->myUrl],
            ['room_id' => $roomId, 'customer_name' => $customerName, 'status' => 'committed', 'created_at' => now(), 'updated_at' => now()]
        );
    }

    private function getOtherNodes(): array
    {
        return array_filter($this->allNodes, fn($url) => !str_contains($url, $this->myUrl));
    }

    // ── HELPER: GHI NHẬT KÝ ─────────────────────────────────────
    private function logSystem($txnId, string $action, string $details, string $status = 'info'): void
    {
        try {
            DB::table('node_logs')->insert([
                'node_port'      => request()->getHost(),
                'transaction_id' => (string) $txnId,
                'action'         => $action,
                'details'        => $details,
                'status'         => $status,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        } catch (\Exception $e) {
            // Không để lỗi ghi log làm hỏng logic chính
        }
    }
}
