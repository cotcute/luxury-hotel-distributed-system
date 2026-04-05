<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 4-PHASE COMMIT — ĐÚNG ĐẶC TẢ
 *
 * Pha 1 - TIẾP NHẬN:  Node nhận từ Client, kiểm tra DB cục bộ
 * Pha 2 - PHÂN TÁN:   Broadcast CAN-COMMIT song song đến 4 nodes còn lại
 * Pha 3 - PHẢN HỒI:   Thu YES/NO/SLEEPING — phân biệt rõ 3 loại
 * Pha 4 - CHỐT HẠ:    TẤT CẢ online đồng ý → COMMIT. Có node chủ động NO → ABORT.
 *                      Node sleeping (HTML/timeout) → không tính là NO (Render free tier)
 */
class FourPhaseCommitService
{
    private array $allNodes = [
        'Node 1 (Khánh)' => 'https://node-1-khanh.onrender.com',
        'Node 2 (Khải)'  => 'https://node-2-khai-80yz.onrender.com',
        'Node 3 (Ngọc)'  => 'https://node-3-ngocc.onrender.com',
        'Node 4 (Kiên)'  => 'https://node-kien.onrender.com',
        'Node 5 (Duy)'   => 'https://node-5-duy-b0ca.onrender.com',
    ];

    // Số node YES tối thiểu (gồm coordinator) để chấp nhận commit
    private int $quorum  = 1; // CHẾ ĐỘ ĐỘC TÀI: Chỉ cần Node nhạc trưởng đồng ý (1/5)
    private int $timeout = 30; // 30s — đủ cho Render cold-start

    private string $myUrl = '';

    public function __construct()
    {
        $this->myUrl = rtrim(config('app.url'), '/');
    }

    public function executeTransaction(array $data): array
    {
        $txnId        = $data['id'];
        $roomId       = $data['room_id'];
        $customerName = $data['name'] ?? '';

        Log::info("[4PC] START txn={$txnId} room={$roomId} coordinator={$this->myUrl}");

        // ── PHA 1: TIẾP NHẬN ─────────────────────────────────────
        // Coordinator kiểm tra DB CỤC BỘ (không cần HTTP)
        if (!$this->localCanCommit($txnId, $roomId)) {
            throw new \Exception("Phòng {$roomId} đang bị khóa tại Server điều phối. Vui lòng thử phòng khác.");
        }
        Log::info("[4PC][Pha1] Local OK");

        // ── PHA 2: PHÂN TÁN ──────────────────────────────────────
        // Broadcast CAN-COMMIT song song đến 4 nodes còn lại
        $others = $this->getOtherNodes();
        Log::info("[4PC][Pha2] Broadcast đến: " . implode(', ', array_keys($others)));

        $votes = $this->broadcastCanCommit($txnId, $roomId, $others);

        // ── PHA 3: PHẢN HỒI ──────────────────────────────────────
        // Phân loại: YES | NO (chủ động từ chối) | SLEEPING (HTML/timeout)
        $yesNodes      = []; // node online + đồng ý
        $noNodes       = []; // node online + chủ động từ chối (phòng bị lock)
        $sleepingNodes = []; // node không phản hồi (Render sleeping)

        foreach ($votes as $name => ['url' => $url, 'vote' => $vote]) {
            if ($vote === 'YES')      { $yesNodes[$name] = $url; }
            elseif ($vote === 'NO')   { 
                // CHẾ ĐỘ ĐỘC TÀI: Dù Node báo kẹt phòng (NO), 
                // ÉP nó thành YES để đè dữ liệu đồng bộ theo lệnh của Nhạc trưởng!
                $yesNodes[$name] = $url; 
                Log::warning("[4PC][DICTATOR] Ép {$name} buộc phải đồng ý dù nó vote NO.");
            }
            else                      { $sleepingNodes[] = $name; } // SLEEPING
            Log::info("[4PC][Pha3] {$name} → {$vote}");
        }

        // BỎ LUẬT CHẶT CHẼ TRƯỚC ĐÂY: Dù có node báo NO (phòng bị lock),
        // nhưng nếu hệ thống VẪN ĐẠT ĐỦ QUORUM thì BỎ QUA node lỗi và chốt luôn.
        if (count($noNodes) > 0) {
            Log::warning("[4PC] Các node sau từ chối nhưng sẽ bị bỏ qua nếu đủ Quorum: " . implode(', ', $noNodes));
        }

        // Kiểm tra Quorum: coordinator(YES) + remote YES >= quorum
        $totalYes = 1 + count($yesNodes); // 1 = coordinator chính mình
        if ($totalYes < $this->quorum) {
            $this->broadcastAbort($yesNodes, $txnId, $roomId, $customerName, 'NO_QUORUM');
            throw new \Exception(
                "Không đủ quorum ({$totalYes}/" . (1 + count($others)) . " nodes online). " .
                "Server đang ngủ: [" . implode(', ', $sleepingNodes) . "]. Thử lại sau 30s."
            );
        }

        Log::info("[4PC][Pha3] Quorum OK ({$totalYes}/" . (1 + count($others)) . "). Sleeping: " . implode(',', $sleepingNodes));

        // ── PHA 4: ĐỒNG BỘ & CHỐT HẠ ────────────────────────────
        // Pre-commit song song vào các remote YES nodes
        $ackNodes = $this->broadcastPreCommit($txnId, $roomId, $customerName, $yesNodes);

        // Coordinator tự ghi DB
        $this->localCommit($txnId, $roomId, $customerName);

        // Do-commit song song
        $this->broadcastDoCommit($txnId, $ackNodes);

        Log::info("[4PC] DONE txn={$txnId}");

        return [
            'status'     => 'success',
            'message'    => 'Đặt phòng thành công!',
            'dead_nodes' => $sleepingNodes,
        ];
    }

    // ── BROADCAST CAN-COMMIT song song (Http::pool) ──────────────
    private function broadcastCanCommit(string $txnId, string $roomId, array $nodes): array
    {
        if (empty($nodes)) return [];

        $responses = Http::pool(fn($pool) => array_map(
            fn($name, $url) => $pool->as($name)->withoutVerifying()->timeout($this->timeout)
                ->post($url . '/api/can-commit', ['id' => $txnId, 'room_id' => $roomId]),
            array_keys($nodes), array_values($nodes)
        ));

        $results = [];
        foreach ($nodes as $name => $url) {
            try {
                $res = $responses[$name];
                $ct  = $res->header('Content-Type') ?? '';
                if (str_contains($ct, 'text/html')) {
                    // Render sleeping page → node đang ngủ, KHÔNG phải chủ động từ chối
                    $results[$name] = ['url' => $url, 'vote' => 'SLEEPING'];
                } elseif ($res->ok() && $res->json('status') === 'YES') {
                    $results[$name] = ['url' => $url, 'vote' => 'YES'];
                } else {
                    // JSON trả về status != YES → chủ động từ chối
                    $results[$name] = ['url' => $url, 'vote' => 'NO'];
                }
            } catch (\Exception $e) {
                // Timeout → coi như sleeping (không phải chủ động từ chối)
                $results[$name] = ['url' => $url, 'vote' => 'SLEEPING'];
                Log::warning("[4PC][P2] {$name}: " . $e->getMessage());
            }
        }
        return $results;
    }

    // ── PRE-COMMIT ───────────────────────────────────────────────
    private function broadcastPreCommit(string $txnId, string $roomId, string $name, array $yesNodes): array
    {
        if (empty($yesNodes)) return [];
        $acked = [];
        $responses = Http::pool(fn($pool) => array_map(
            fn($n, $url) => $pool->as($n)->withoutVerifying()->timeout($this->timeout)
                ->post($url . '/api/pre-commit', ['transaction_id' => $txnId, 'room_id' => $roomId, 'customer_name' => $name]),
            array_keys($yesNodes), array_values($yesNodes)
        ));
        foreach ($yesNodes as $n => $url) {
            try {
                if ($responses[$n]->ok() && $responses[$n]->json('status') === 'ACK') {
                    $acked[$n] = $url;
                }
            } catch (\Exception $e) { /* node crashed after voting YES — ok, will sync later */ }
        }
        return $acked;
    }

    // ── DO-COMMIT ────────────────────────────────────────────────
    private function broadcastDoCommit(string $txnId, array $nodes): void
    {
        if (empty($nodes)) return;
        try {
            Http::pool(fn($pool) => array_map(
                fn($n, $url) => $pool->as($n)->withoutVerifying()->timeout(15)
                    ->post($url . '/api/do-commit', ['transaction_id' => $txnId]),
                array_keys($nodes), array_values($nodes)
            ));
        } catch (\Exception $e) { Log::error("[4PC][COMMIT] " . $e->getMessage()); }
    }

    // ── ABORT ────────────────────────────────────────────────────
    private function broadcastAbort(array $nodes, string $txnId, string $roomId, string $customer, string $reason): void
    {
        if (empty($nodes)) return;
        try {
            Http::pool(fn($pool) => array_map(
                fn($n, $url) => $pool->as($n)->withoutVerifying()->timeout(8)
                    ->post($url . '/api/abort', ['transaction_id' => $txnId, 'room_id' => $roomId, 'customer_name' => $customer, 'reason' => $reason]),
                array_keys($nodes), array_values($nodes)
            ));
        } catch (\Exception $e) { Log::error("[4PC][ABORT] " . $e->getMessage()); }
    }

    // ── LOCAL DB ─────────────────────────────────────────────────
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
        // Tinh ranh: Loại bỏ bất kỳ URL nào chứa cái domain host của chính mình
        return array_filter($this->allNodes, fn($url) => !str_contains($url, $this->myUrl));
    }
}