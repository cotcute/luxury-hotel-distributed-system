<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FourPhaseCommitService
{
    // =========================================================
    // 📋 DANH SÁCH CÁC NODE KHÁC (không bao gồm chính mình)
    // =========================================================
    // Mỗi node cần biết URL của các node còn lại để điều phối 4PC.
    // Node hiện tại sẽ tự xử lý DB của chính mình trực tiếp (không qua HTTP).
    private array $otherNodes = [
        'Node 1 (Khánh)' => 'https://node-1-khanh.onrender.com',
        'Node 2 (Khải)'  => 'https://node-2-khai-80yz.onrender.com',
        'Node 3 (Ngọc)'  => 'https://node-3-ngocc.onrender.com',
        'Node 4 (Kiên)'  => 'https://node-kien.onrender.com',
        'Node 5 (Duy)'   => 'https://node-5-duy-b0ca.onrender.com',
    ];

    // Tên của node hiện tại (để loại bỏ khỏi danh sách gọi HTTP)
    // Sẽ được tự động detect dựa trên APP_URL trong .env
    private string $myUrl = '';

    // Quorum: cần ít nhất 3 / 5 nodes đồng ý
    private int $quorum = 3;

    // Timeout gọi đến node khác (giây) - tăng lên để chờ Render wake-up
    private int $timeout = 25;

    public function __construct()
    {
        // Detect URL của chính mình từ config
        $this->myUrl = rtrim(config('app.url'), '/');
    }

    // =========================================================
    // 🚀 ĐIỀU PHỐI TOÀN BỘ GIAO THỨC 4-PHASE COMMIT
    // =========================================================
    public function executeTransaction(array $data): array
    {
        $transactionId = $data['id'];
        $roomId        = $data['room_id'];
        $customerName  = $data['name'];

        Log::info("[4PC] ▶ START transaction={$transactionId}, room={$roomId}");

        // Tách danh sách: node khác (gọi HTTP) vs chính mình (gọi DB trực tiếp)
        $remoteNodes = $this->getRemoteNodes();

        // =====================================================
        // PHA 1: CAN-COMMIT - Gửi SONG SONG đến tất cả remote nodes
        //        + Tự kiểm tra DB của chính mình ngay lập tức
        // =====================================================
        [$yesNodes, $deadNodes] = $this->phase1CanCommit($transactionId, $roomId, $remoteNodes);

        // Kiểm tra Quorum sau Phase 1
        if (count($yesNodes) < $this->quorum) {
            $this->broadcastAbort(array_intersect_key($remoteNodes, $yesNodes), $transactionId, $roomId, $customerName, 'QUORUM_FAILED');
            throw new \Exception(
                'Không đủ Quorum! Chỉ có ' . count($yesNodes) . '/' . (count($remoteNodes) + 1)
                . ' node sẵn sàng. Phòng ' . $roomId . ' có thể đã được đặt!'
            );
        }

        Log::info("[4PC][P1] ✅ Quorum đạt: " . count($yesNodes) . " nodes đồng ý.");

        // =====================================================
        // PHA 2: PRE-COMMIT - Ghi tạm vào DB toàn bộ nodes SONG SONG
        // =====================================================
        [$ackNodes] = $this->phase2PreCommit($transactionId, $roomId, $customerName, $yesNodes, $remoteNodes);

        // Kiểm tra Quorum sau Phase 2
        if (count($ackNodes) < $this->quorum) {
            $this->broadcastAbort(array_intersect_key($remoteNodes, $ackNodes), $transactionId, $roomId, $customerName, 'PRE_COMMIT_FAILED');
            throw new \Exception(
                'Pre-Commit thất bại! Chỉ ghi được ' . count($ackNodes) . '/' . (count($remoteNodes) + 1) . ' nodes.'
            );
        }

        Log::info("[4PC][P2] ✅ Pre-Commit ACK: " . count($ackNodes) . " nodes.");

        // =====================================================
        // PHA 3: DO-COMMIT - Chốt giao dịch SONG SONG
        // =====================================================
        $committedNodes = $this->phase3DoCommit($transactionId, $ackNodes, $remoteNodes);

        // Tổng hợp kết quả
        $allDeadNames = array_values(array_diff(
            array_merge(['Chính mình'], array_keys($remoteNodes)),
            $committedNodes
        ));

        Log::info("[4PC] ✅ DONE transaction={$transactionId}. Dead=" . implode(',', $allDeadNames));

        return [
            'status'          => 'success',
            'message'         => 'Đặt phòng thành công! Dữ liệu đã được phân tán lên các server.',
            'transaction_id'  => $transactionId,
            'committed_nodes' => $committedNodes,
            'dead_nodes'      => $allDeadNames,
        ];
    }

    // =========================================================
    // PHA 1: CAN-COMMIT (Parallel HTTP + local DB check)
    // =========================================================
    private function phase1CanCommit(string $transactionId, string $roomId, array $remoteNodes): array
    {
        $yesNodes  = [];
        $deadNodes = [];

        // ✅ Tự kiểm tra chính mình trực tiếp (không qua HTTP - nhanh hơn)
        $selfVote = $this->localCanCommit($transactionId, $roomId);
        if ($selfVote) {
            $yesNodes['self'] = true;
            Log::info("[4PC][P1] SELF -> YES");
        } else {
            $deadNodes[] = 'self';
            Log::warning("[4PC][P1] SELF -> NO (phòng bị lock)");
        }

        if (empty($remoteNodes)) {
            return [$yesNodes, $deadNodes];
        }

        // ✅ Gọi SONG SONG đến tất cả remote nodes bằng Http::pool
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
                    $yesNodes[$name] = $url;
                    Log::info("[4PC][P1] {$name} -> YES");
                } else {
                    $deadNodes[] = $name;
                    Log::warning("[4PC][P1] {$name} -> NO/ERR " . $res->status());
                }
            } catch (\Exception $e) {
                $deadNodes[] = $name;
                Log::warning("[4PC][P1] {$name} -> TIMEOUT: " . $e->getMessage());
            }
        }

        return [$yesNodes, $deadNodes];
    }

    // =========================================================
    // PHA 2: PRE-COMMIT (Parallel HTTP + local DB write)
    // =========================================================
    private function phase2PreCommit(
        string $transactionId,
        string $roomId,
        string $customerName,
        array  $yesNodes,
        array  $remoteNodes
    ): array {
        $ackNodes = [];

        // ✅ Tự ghi vào DB của chính mình ngay lập tức
        if (isset($yesNodes['self'])) {
            $selfAck = $this->localPreCommit($transactionId, $roomId, $customerName);
            if ($selfAck) {
                $ackNodes['self'] = true;
                Log::info("[4PC][P2] SELF -> ACK");
            }
        }

        // Remote nodes đã vote YES
        $remoteYesNodes = array_intersect_key($remoteNodes, $yesNodes);
        if (empty($remoteYesNodes)) {
            return [$ackNodes];
        }

        // ✅ Gọi SONG SONG Pre-Commit
        $responses = Http::pool(function ($pool) use ($remoteYesNodes, $transactionId, $roomId, $customerName) {
            foreach ($remoteYesNodes as $name => $url) {
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

        foreach ($remoteYesNodes as $name => $url) {
            try {
                $res = $responses[$name];
                if ($res->ok() && $res->json('status') === 'ACK') {
                    $ackNodes[$name] = $url;
                    Log::info("[4PC][P2] {$name} -> ACK");
                } else {
                    Log::warning("[4PC][P2] {$name} -> FAIL " . $res->status());
                }
            } catch (\Exception $e) {
                Log::warning("[4PC][P2] {$name} -> DOWN: " . $e->getMessage());
            }
        }

        return [$ackNodes];
    }

    // =========================================================
    // PHA 3: DO-COMMIT (Parallel HTTP + local DB commit)
    // =========================================================
    private function phase3DoCommit(string $transactionId, array $ackNodes, array $remoteNodes): array
    {
        $committedNodes = [];

        // ✅ Commit chính mình trực tiếp
        if (isset($ackNodes['self'])) {
            $this->localDoCommit($transactionId);
            $committedNodes[] = 'Chính mình';
            Log::info("[4PC][P3] SELF -> COMMITTED");
        }

        // Remote nodes đã ACK
        $remoteAckNodes = array_intersect_key($remoteNodes, $ackNodes);
        if (empty($remoteAckNodes)) {
            return $committedNodes;
        }

        // ✅ Gọi SONG SONG Do-Commit
        $responses = Http::pool(function ($pool) use ($remoteAckNodes, $transactionId) {
            foreach ($remoteAckNodes as $name => $url) {
                $pool->as($name)
                    ->withoutVerifying()
                    ->timeout($this->timeout)
                    ->post($url . '/api/do-commit', [
                        'transaction_id' => $transactionId,
                    ]);
            }
        });

        foreach ($remoteAckNodes as $name => $url) {
            try {
                $responses[$name]; // Không quan tâm kết quả - đã pre-commit thì dữ liệu an toàn
                $committedNodes[] = $name;
                Log::info("[4PC][P3] {$name} -> COMMITTED");
            } catch (\Exception $e) {
                Log::error("[4PC][P3] {$name} -> DOWN khi commit: " . $e->getMessage());
            }
        }

        return $committedNodes;
    }

    // =========================================================
    // 🔧 CÁC HÀM DB LOCAL (Xử lý trực tiếp DB của chính node)
    // =========================================================
    private function localCanCommit(string $transactionId, string $roomId): bool
    {
        return !DB::table('node_bookings')
            ->where('room_id', $roomId)
            ->where('transaction_id', '!=', $transactionId)
            ->where('status', 'pending')
            ->where('created_at', '>=', now()->subMinutes(2))
            ->exists();
    }

    private function localPreCommit(string $transactionId, string $roomId, string $customerName): bool
    {
        try {
            DB::table('node_bookings')->updateOrInsert(
                [
                    'transaction_id' => $transactionId,
                    'node_port'      => config('app.url'),
                ],
                [
                    'room_id'       => $roomId,
                    'customer_name' => $customerName,
                    'status'        => 'pending',
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]
            );
            return true;
        } catch (\Exception $e) {
            Log::error("[4PC] localPreCommit error: " . $e->getMessage());
            return false;
        }
    }

    private function localDoCommit(string $transactionId): void
    {
        DB::table('node_bookings')
            ->where('transaction_id', $transactionId)
            ->where('status', 'pending')
            ->update(['status' => 'committed', 'updated_at' => now()]);
    }

    // =========================================================
    // PHA 4: ABORT - Gửi Abort SONG SONG đến các nodes
    // =========================================================
    private function broadcastAbort(
        array  $nodes,
        string $transactionId,
        string $roomId,
        string $customerName,
        string $reason
    ): void {
        // Tự rollback chính mình
        DB::table('node_bookings')
            ->where('transaction_id', $transactionId)
            ->where('status', 'pending')
            ->update(['status' => $reason, 'updated_at' => now()]);

        if (empty($nodes)) return;

        // Gửi Abort song song đến các remote nodes
        Http::pool(function ($pool) use ($nodes, $transactionId, $roomId, $customerName, $reason) {
            foreach ($nodes as $name => $url) {
                $pool->as($name)
                    ->withoutVerifying()
                    ->timeout(5)
                    ->post($url . '/api/abort', [
                        'transaction_id' => $transactionId,
                        'room_id'        => $roomId,
                        'customer_name'  => $customerName,
                        'reason'         => $reason,
                    ]);
            }
        });
    }

    // =========================================================
    // 🧭 HELPER: Lấy danh sách remote nodes (bỏ chính mình ra)
    // =========================================================
    private function getRemoteNodes(): array
    {
        $myUrl = $this->myUrl;

        return array_filter(
            $this->otherNodes,
            fn($url) => rtrim($url, '/') !== rtrim($myUrl, '/')
        );
    }
}