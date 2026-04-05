<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FourPhaseCommitService
{
    // =========================================================
    // 📋 DANH SÁCH TẤT CẢ NODES TRONG HỆ THỐNG
    // =========================================================
    private array $allNodes = [
        'Node 1 (Khánh)' => 'https://node-1-khanh.onrender.com',
        'Node 2 (Khải)'  => 'https://node-2-khai-80yz.onrender.com',
        'Node 3 (Ngọc)'  => 'https://node-3-ngocc.onrender.com',
        'Node 4 (Kiên)'  => 'https://node-kien.onrender.com',
        'Node 5 (Duy)'   => 'https://node-5-duy-b0ca.onrender.com',
    ];

    // URL Center Server để ghi lại booking sau khi thành công
    private string $centerUrl = 'https://luxury-hotel-cente.onrender.com';

    // Quorum: cần ít nhất 3 / 5 nodes đồng ý
    private int $quorum = 3;

    // Timeout cho từng request đến node (giây)
    private int $timeout = 12;

    // =========================================================
    // 🚀 ĐIỀU PHỐI TOÀN BỘ GIAO THỨC 4-PHASE COMMIT
    // =========================================================
    public function executeTransaction(array $data): array
    {
        $transactionId = $data['id'];
        $roomId        = $data['room_id'];
        $customerName  = $data['name'];

        Log::info("[4PC] Bắt đầu transaction: {$transactionId}, phòng: {$roomId}");

        // =====================================================
        // PHA 1: CAN-COMMIT (Voting - Hỏi ý kiến toàn bộ nodes)
        // =====================================================
        $yesNodes  = []; // Nodes đồng ý
        $deadNodes = []; // Nodes không phản hồi hoặc từ chối

        foreach ($this->allNodes as $name => $url) {
            try {
                $response = Http::withoutVerifying()
                    ->timeout($this->timeout)
                    ->post($url . '/api/can-commit', [
                        'id'      => $transactionId,
                        'room_id' => $roomId,
                    ]);

                if ($response->ok() && $response->json('status') === 'YES') {
                    $yesNodes[$name] = $url;
                    Log::info("[4PC][Phase1] {$name} -> YES");
                } else {
                    $deadNodes[] = $name;
                    Log::warning("[4PC][Phase1] {$name} -> NO/Error: " . $response->body());
                }

            } catch (\Exception $e) {
                $deadNodes[] = $name;
                Log::warning("[4PC][Phase1] {$name} -> TIMEOUT/DOWN: " . $e->getMessage());
            }
        }

        // Kiểm tra Quorum sau Phase 1
        if (count($yesNodes) < $this->quorum) {
            $this->broadcastAbort($yesNodes, $transactionId, $roomId, $customerName, 'QUORUM_FAILED');
            throw new \Exception(
                'Không đủ Quorum! Chỉ có ' . count($yesNodes) . '/' . count($this->allNodes)
                . ' node đồng ý. Phòng có thể đã được đặt hoặc hệ thống quá tải!'
            );
        }

        Log::info("[4PC][Phase1] Đạt Quorum: " . count($yesNodes) . "/" . count($this->allNodes) . " nodes đồng ý.");

        // =====================================================
        // PHA 2: PRE-COMMIT (Ghi tạm vào DB - Chuẩn bị Commit)
        // =====================================================
        $ackNodes = []; // Nodes đã ghi tạm thành công

        foreach ($yesNodes as $name => $url) {
            try {
                $response = Http::withoutVerifying()
                    ->timeout($this->timeout)
                    ->post($url . '/api/pre-commit', [
                        'transaction_id' => $transactionId,
                        'room_id'        => $roomId,
                        'customer_name'  => $customerName,
                    ]);

                if ($response->ok() && $response->json('status') === 'ACK') {
                    $ackNodes[$name] = $url;
                    Log::info("[4PC][Phase2] {$name} -> ACK");
                } else {
                    Log::warning("[4PC][Phase2] {$name} -> FAILED: " . $response->body());
                }

            } catch (\Exception $e) {
                // Node chết sau khi vote YES - ghi log nhưng tiếp tục
                Log::warning("[4PC][Phase2] {$name} -> DOWN sau vote: " . $e->getMessage());
            }
        }

        // Kiểm tra Quorum sau Phase 2
        if (count($ackNodes) < $this->quorum) {
            $this->broadcastAbort($ackNodes, $transactionId, $roomId, $customerName, 'PRE_COMMIT_FAILED');
            throw new \Exception(
                'Pre-Commit thất bại! Không đủ ACK (' . count($ackNodes) . '/' . count($this->allNodes) . ').'
            );
        }

        Log::info("[4PC][Phase2] Đạt Quorum ACK: " . count($ackNodes) . " nodes sẵn sàng commit.");

        // =====================================================
        // PHA 3: DO-COMMIT (Chốt giao dịch chính thức)
        // =====================================================
        $committedNodes = [];

        foreach ($ackNodes as $name => $url) {
            try {
                $response = Http::withoutVerifying()
                    ->timeout($this->timeout)
                    ->post($url . '/api/do-commit', [
                        'transaction_id' => $transactionId,
                    ]);

                $committedNodes[] = $name;
                Log::info("[4PC][Phase3] {$name} -> COMMITTED");

            } catch (\Exception $e) {
                // Node tắt ngay lúc commit - dữ liệu vẫn an toàn ở phase 2
                // Node sẽ tự recover và force-sync sau
                Log::error("[4PC][Phase3] {$name} -> DOWN khi commit: " . $e->getMessage());
            }
        }

        // Tính các node bị chết hoàn toàn (kể cả dead từ phase 1)
        $allDeadNames = array_merge(
            $deadNodes,
            array_values(array_diff(array_keys($yesNodes), array_keys($ackNodes)))
        );

        Log::info("[4PC] ✅ HOÀN THÀNH! Transaction: {$transactionId}. Dead nodes: " . implode(', ', $allDeadNames));

        return [
            'status'          => 'success',
            'message'         => 'Đặt phòng thành công! Dữ liệu đã được đồng bộ lên hệ thống phân tán.',
            'transaction_id'  => $transactionId,
            'committed_nodes' => $committedNodes,
            'dead_nodes'      => $allDeadNames,
        ];
    }

    // =========================================================
    // PHA 4: ABORT (Rollback - Dọn dẹp khi thất bại)
    // =========================================================
    private function broadcastAbort(
        array  $nodes,
        string $transactionId,
        string $roomId,
        string $customerName,
        string $reason
    ): void {
        foreach ($nodes as $name => $url) {
            try {
                Http::withoutVerifying()
                    ->timeout(5)
                    ->post($url . '/api/abort', [
                        'transaction_id' => $transactionId,
                        'room_id'        => $roomId,
                        'customer_name'  => $customerName,
                        'reason'         => $reason,
                    ]);
                Log::info("[4PC][Abort] {$name} -> ABORTED ({$reason})");
            } catch (\Exception $e) {
                Log::warning("[4PC][Abort] {$name} -> không thể abort: " . $e->getMessage());
            }
        }
    }
}