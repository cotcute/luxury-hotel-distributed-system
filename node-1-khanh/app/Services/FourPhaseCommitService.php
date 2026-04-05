<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ╔══════════════════════════════════════════════════════╗
 * ║         4-PHASE COMMIT — ĐÚNG ĐẶC TẢ ĐỀ BÀI         ║
 * ╠══════════════════════════════════════════════════════╣
 * ║ Pha 1 - TIẾP NHẬN:  Node nhận request từ Client     ║
 * ║                      Kiểm tra DB cục bộ của chính mình ║
 * ║ Pha 2 - PHÂN TÁN:   Broadcast CAN-COMMIT đến 4 node ║
 * ║                      còn lại (song song, không chờ) ║
 * ║ Pha 3 - PHẢN HỒI:   Thu YES/NO từ 4 nodes           ║
 * ║                      TẤT CẢ 4 YES → Commit           ║
 * ║                      1 NO bất kỳ → Abort toàn bộ    ║
 * ║ Pha 4 - ĐỒNG BỘ:    [TỐT] Coordinator ghi DB mình  ║
 * ║                      + gửi DO-COMMIT song song       ║
 * ║                      [XẤU] Gửi ABORT → dọn lock     ║
 * ╚══════════════════════════════════════════════════════╝
 */
class FourPhaseCommitService
{
    // =========================================================
    // DANH SÁCH TẤT CẢ 5 NODES
    // Coordinator tự nhận biết mình qua APP_URL và loại ra khi broadcast
    // =========================================================
    private array $allNodes = [
        'Node 1 (Khánh)' => 'https://node-1-khanh.onrender.com',
        'Node 2 (Khải)'  => 'https://node-2-khai-80yz.onrender.com',
        'Node 3 (Ngọc)'  => 'https://node-3-ngocc.onrender.com',
        'Node 4 (Kiên)'  => 'https://node-kien.onrender.com',
        'Node 5 (Duy)'   => 'https://node-5-duy-b0ca.onrender.com',
    ];

    private string $myUrl   = '';
    private int    $timeout = 25; // 25s — cho phép Render cold-start ~15-20s

    public function __construct()
    {
        $this->myUrl = rtrim(config('app.url'), '/');
    }

    // =========================================================
    // ENTRY POINT: Điều phối toàn bộ giao dịch 4 pha
    // =========================================================
    public function executeTransaction(array $data): array
    {
        $transactionId = $data['id'];
        $roomId        = $data['room_id'];
        $customerName  = $data['name'] ?? '';

        Log::info("[4PC] ▶ START | txn={$transactionId} | room={$roomId} | coordinator={$this->myUrl}");

        // Lấy 4 nodes còn lại (bỏ chính mình ra)
        $remoteNodes = $this->getOtherNodes();

        // =====================================================
        // PHA 1: TIẾP NHẬN (Request Phase)
        // =====================================================
        // Coordinator tự kiểm tra DB CỤC BỘ của mình (không cần HTTP)
        // Nếu phòng đang bị lock → báo thất bại ngay lập tức
        // =====================================================
        Log::info("[4PC][Pha1] Kiểm tra DB cục bộ...");

        if (!$this->localCanCommit($transactionId, $roomId)) {
            throw new \Exception(
                "Phòng {$roomId} đang bị KHÓA tại Server Điều phối ({$this->myUrl})! Không thể tiếp tục."
            );
        }

        Log::info("[4PC][Pha1] ✅ DB cục bộ OK → Bắt đầu Pha 2 (Phân tán)...");

        // =====================================================
        // PHA 2: PHÂN TÁN (Prepare Phase — Broadcast SONG SONG)
        // =====================================================
        // Gửi CAN-COMMIT đến 4 nodes còn lại ĐỒNG THỜI (Http::pool)
        // Mỗi node: kiểm tra DB của họ → trả YES hoặc NO
        // =====================================================
        Log::info("[4PC][Pha2] 📡 Broadcast CAN-COMMIT đến: " . implode(', ', array_keys($remoteNodes)));

        $canCommitResponses = $this->broadcastCanCommit($transactionId, $roomId, $remoteNodes);

        // =====================================================
        // PHA 3: PHẢN HỒI (Vote Collection)
        // =====================================================
        // Tổng hợp vote. Quy tắc: TẤT CẢ 4 nodes phải YES.
        // Dù chỉ 1 node báo NO hoặc không phản hồi → ABORT
        // =====================================================
        Log::info("[4PC][Pha3] Tổng hợp vote...");

        $yesNodes  = [];
        $noNodes   = [];
        $deadNodes = [];

        foreach ($canCommitResponses as $name => ['url' => $url, 'vote' => $vote, 'dead' => $dead]) {
            if ($vote === 'YES') {
                $yesNodes[$name] = $url;
                Log::info("[4PC][Pha3] {$name} → YES ✅");
            } else {
                $noNodes[] = $name;
                if ($dead) $deadNodes[] = $name;
                Log::warning("[4PC][Pha3] {$name} → NO/TIMEOUT ❌");
            }
        }

        // TẤT CẢ phải đồng ý — dù 1 NO → ABORT
        if (count($noNodes) > 0) {
            Log::warning("[4PC][Pha3] ⛔ Có node từ chối: [" . implode(', ', $noNodes) . "]. Đang ABORT...");

            // Gửi ABORT đến các node đã YES để dọn lock
            $this->broadcastAbortParallel($yesNodes, $transactionId, $roomId, $customerName, 'VOTED_NO_BY_PEER');

            // Tạo thông điệp phân biệt dead vs refused
            if (count($deadNodes) > 0) {
                $msg = 'Server [' . implode(', ', $deadNodes) . '] không phản hồi (đang ngủ đông hoặc sập).';
            } else {
                $msg = 'Server [' . implode(', ', $noNodes) . '] từ chối vì phòng đã được đặt hoặc đang bận.';
            }

            throw new \Exception("Giao dịch HỦY! {$msg} Vui lòng thử lại sau.");
        }

        Log::info("[4PC][Pha3] ✅ TẤT CẢ " . count($yesNodes) . " nodes đồng ý! → Pha 4 (Chốt hạ)...");

        // =====================================================
        // PHA 4: ĐỒNG BỘ & CHỐT HẠ — Trường hợp TỐT
        // =====================================================
        // 1. PRE-COMMIT song song (ghi pending lock vào tất cả nodes)
        // 2. Coordinator tự ghi committed vào DB CỤC BỘ của mình
        // 3. Gửi DO-COMMIT song song đến 4 nodes còn lại
        // =====================================================
        Log::info("[4PC][Pha4] 🎯 Đang PRE-COMMIT song song...");

        $ackResponses = $this->broadcastPreCommit($transactionId, $roomId, $customerName, $yesNodes);
        $failedAck    = array_filter($ackResponses, fn($r) => $r !== 'ACK');

        if (count($failedAck) > 0) {
            // Ít có khả năng xảy ra — nhưng nếu có thì abort an toàn
            Log::warning("[4PC][Pha4] Pre-Commit thất bại tại: " . implode(', ', array_keys($failedAck)));
            $this->broadcastAbortParallel($yesNodes, $transactionId, $roomId, $customerName, 'PRE_COMMIT_FAILED');
            throw new \Exception('Pre-Commit thất bại! Hệ thống đã Rollback an toàn. Vui lòng thử lại.');
        }

        // Coordinator tự ghi DB của MÌNH
        $this->localCommit($transactionId, $roomId, $customerName);
        Log::info("[4PC][Pha4] Coordinator tự ghi DB: ✅");

        // Gửi DO-COMMIT song song đến 4 nodes
        $this->broadcastDoCommit($transactionId, $yesNodes);
        Log::info("[4PC][Pha4] ✅ DO-COMMIT gửi đến tất cả nodes → HOÀN THÀNH!");

        return [
            'status'          => 'success',
            'message'         => 'Đặt phòng thành công! Tất cả 5 server đã đồng bộ dữ liệu.',
            'transaction_id'  => $transactionId,
            'committed_nodes' => array_merge(['Coordinator'], array_keys($yesNodes)),
            'dead_nodes'      => [],
        ];
    }

    // =========================================================
    // PHA 2: Gửi CAN-COMMIT SONG SONG đến tất cả remote nodes
    // =========================================================
    private function broadcastCanCommit(string $transactionId, string $roomId, array $remoteNodes): array
    {
        if (empty($remoteNodes)) {
            return [];
        }

        $results   = [];
        $responses = Http::pool(function ($pool) use ($remoteNodes, $transactionId, $roomId) {
            foreach ($remoteNodes as $name => $url) {
                $pool->as($name)
                    ->withoutVerifying()
                    ->timeout($this->timeout)
                    ->post($url . '/api/can-commit', [
                        'id'      => $transactionId,
                        'room_id' => $roomId,
                    ]);
            }
        });

        foreach ($remoteNodes as $name => $url) {
            try {
                $res = $responses[$name];
                if ($res->ok() && $res->json('status') === 'YES') {
                    $results[$name] = ['url' => $url, 'vote' => 'YES', 'dead' => false];
                } else {
                    $results[$name] = ['url' => $url, 'vote' => 'NO',  'dead' => false];
                }
            } catch (\Exception $e) {
                $results[$name] = ['url' => $url, 'vote' => 'NO', 'dead' => true];
                Log::error("[4PC][CAN-COMMIT] {$name}: " . $e->getMessage());
            }
        }

        return $results;
    }

    // =========================================================
    // PHA 4a: Gửi PRE-COMMIT SONG SONG
    // =========================================================
    private function broadcastPreCommit(
        string $transactionId,
        string $roomId,
        string $customerName,
        array  $yesNodes
    ): array {
        if (empty($yesNodes)) return [];

        $results   = [];
        $responses = Http::pool(function ($pool) use ($yesNodes, $transactionId, $roomId, $customerName) {
            foreach ($yesNodes as $name => $url) {
                $pool->as($name)
                    ->withoutVerifying()
                    ->timeout($this->timeout)
                    ->post($url . '/api/pre-commit', [
                        'transaction_id' => $transactionId,
                        'room_id'        => $roomId,
                        'customer_name'  => $customerName,
                    ]);
            }
        });

        foreach ($yesNodes as $name => $url) {
            try {
                $res = $responses[$name];
                $results[$name] = ($res->ok() && $res->json('status') === 'ACK') ? 'ACK' : 'FAILED';
            } catch (\Exception $e) {
                $results[$name] = 'FAILED';
                Log::error("[4PC][PRE-COMMIT] {$name}: " . $e->getMessage());
            }
        }

        return $results;
    }

    // =========================================================
    // PHA 4b: Gửi DO-COMMIT SONG SONG
    // =========================================================
    private function broadcastDoCommit(string $transactionId, array $nodes): void
    {
        if (empty($nodes)) return;

        try {
            Http::pool(function ($pool) use ($nodes, $transactionId) {
                foreach ($nodes as $name => $url) {
                    $pool->as($name)
                        ->withoutVerifying()
                        ->timeout(15)
                        ->post($url . '/api/do-commit', ['transaction_id' => $transactionId]);
                }
            });
        } catch (\Exception $e) {
            Log::error("[4PC][DO-COMMIT] Pool error: " . $e->getMessage());
        }
    }

    // =========================================================
    // PHA ABORT: Gửi ABORT SONG SONG đến nodes đã lock
    // =========================================================
    private function broadcastAbortParallel(
        array  $nodes,
        string $transactionId,
        string $roomId,
        string $customerName,
        string $reason
    ): void {
        if (empty($nodes)) return;

        try {
            Http::pool(function ($pool) use ($nodes, $transactionId, $roomId, $customerName, $reason) {
                foreach ($nodes as $name => $url) {
                    $pool->as($name)
                        ->withoutVerifying()
                        ->timeout(8)
                        ->post($url . '/api/abort', [
                            'transaction_id' => $transactionId,
                            'room_id'        => $roomId,
                            'customer_name'  => $customerName,
                            'reason'         => $reason,
                        ]);
                }
            });
        } catch (\Exception $e) {
            Log::error("[4PC][ABORT] Pool error: " . $e->getMessage());
        }
    }

    // =========================================================
    // LOCAL DB OPERATIONS (Không qua HTTP — nhanh và an toàn)
    // =========================================================
    private function localCanCommit(string $transactionId, string $roomId): bool
    {
        return !DB::table('node_bookings')
            ->where('room_id', $roomId)
            ->where('transaction_id', '!=', $transactionId)
            ->where('status', 'pending')
            ->where('created_at', '>=', now()->subMinutes(5))
            ->exists();
    }

    private function localCommit(string $transactionId, string $roomId, string $customerName): void
    {
        DB::table('node_bookings')->updateOrInsert(
            ['transaction_id' => $transactionId, 'node_port' => $this->myUrl],
            [
                'room_id'       => $roomId,
                'customer_name' => $customerName,
                'status'        => 'committed',
                'created_at'    => now(),
                'updated_at'    => now(),
            ]
        );
    }

    // =========================================================
    // HELPER: Lấy 4 nodes còn lại (loại chính mình)
    // =========================================================
    private function getOtherNodes(): array
    {
        return array_filter(
            $this->allNodes,
            fn($url) => rtrim($url, '/') !== $this->myUrl
        );
    }
}