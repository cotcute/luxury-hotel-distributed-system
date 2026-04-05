<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NodeController extends Controller
{
    // =========================================================
    // 🚀 GIAO TIẾP VỚI CLIENT (NODE LÀM NHẠC TRƯỞNG)
    // =========================================================
    public function receiveFromClient(Request $request)
    {
        $service = new \App\Services\FourPhaseCommitService();

        try {
            $result = $service->executeTransaction($request->all());
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    // =========================================================
    // ENDPOINT: CAN-COMMIT (Pha 1 - Kiểm tra phòng có bị lock không)
    // =========================================================
    public function canCommit(Request $request)
    {
        $rawId         = $request->input('id');
        $transactionId = is_string($rawId) && str_starts_with($rawId, 'txn_') ? crc32($rawId) : (int)$rawId;
        $roomId        = $request->input('room_id');

        $isRoomLocked = DB::table('node_bookings')
            ->where('room_id', $roomId)
            ->where('transaction_id', '!=', $transactionId)
            ->where('status', 'pending')
            ->where('created_at', '>=', now()->subMinutes(5))
            ->exists();

        $vote = $isRoomLocked ? 'NO' : 'YES';

        $this->logSystem(
            $transactionId,
            'PHA 1: TRƯNG CẦU',
            "Nhận CAN-COMMIT từ Coordinator → VOTE {$vote} cho Phòng {$roomId}",
            $vote === 'YES' ? 'success' : 'warning'
        );

        return response()->json(['status' => $vote]);
    }

    // =========================================================
    // ENDPOINT: PRE-COMMIT (Pha 3 - Ghi tạm, chờ hiệu lệnh)
    // =========================================================
    public function preCommit(Request $request)
    {
        $rawId         = $request->input('transaction_id');
        $transactionId = is_string($rawId) && str_starts_with($rawId, 'txn_') ? crc32($rawId) : (int)$rawId;
        $roomId        = $request->input('room_id');
        $customerName  = $request->input('customer_name', '');
        $nodeIdentity  = request()->getHost();

        try {
            DB::table('node_bookings')->updateOrInsert(
                ['transaction_id' => $transactionId, 'node_port' => $nodeIdentity],
                [
                    'room_id'       => $roomId,
                    'customer_name' => $customerName,
                    'status'        => 'pending',
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]
            );

            $this->logSystem(
                $transactionId,
                'PHA 3: KHÓA TẠM',
                "Nhận PRE-COMMIT từ Coordinator → Đã khóa tạm DB, Phòng {$roomId} cho '{$customerName}', chờ hiệu lệnh chốt",
                'info'
            );

            return response()->json(['status' => 'ACK']);
        } catch (\Exception $e) {
            $this->logSystem($transactionId, 'PHA 3: LỖI', 'Pre-Commit thất bại: ' . $e->getMessage(), 'error');
            return response()->json(['status' => 'FAILED', 'error' => $e->getMessage()]);
        }
    }

    // =========================================================
    // ENDPOINT: DO-COMMIT (Pha 4 - Chốt giao dịch chính thức)
    // =========================================================
    public function doCommit(Request $request)
    {
        $rawId         = $request->input('transaction_id');
        $transactionId = is_string($rawId) && str_starts_with($rawId, 'txn_') ? crc32($rawId) : (int)$rawId;
        $roomId        = $request->input('room_id');
        $customerName  = $request->input('customer_name', '');
        $port          = request()->getHost();

        $exists = DB::table('node_bookings')->where('transaction_id', $transactionId)->exists();
        if ($exists) {
            DB::table('node_bookings')->where('transaction_id', $transactionId)->update([
                'status'        => 'committed',
                'room_id'       => $roomId,
                'customer_name' => $customerName,
                'updated_at'    => now(),
            ]);
        } else {
            DB::table('node_bookings')->insert([
                'transaction_id' => $transactionId,
                'node_port'      => $port,
                'room_id'        => $roomId,
                'customer_name'  => $customerName,
                'status'         => 'committed',
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }

        $this->logSystem(
            $transactionId,
            'PHA 4: CHỐT CỨNG',
            "Nhận DO-COMMIT từ Coordinator → Ghi vĩnh viễn Phòng {$roomId} cho '{$customerName}' vào CSDL ✅",
            'success'
        );

        return response()->json(['status' => 'SUCCESS']);
    }

    // =========================================================
    // ENDPOINT: ABORT (Hủy bỏ giao dịch)
    // =========================================================
    public function abort(Request $request)
    {
        $rawId         = $request->input('transaction_id');
        $transactionId = is_string($rawId) && str_starts_with($rawId, 'txn_') ? crc32($rawId) : (int)$rawId;
        $reason        = $request->input('reason', 'ABORTED');
        $roomId        = $request->input('room_id', '');
        $customerName  = $request->input('customer_name', '');
        $nodeIdentity  = request()->getHost();

        DB::table('node_bookings')->updateOrInsert(
            ['transaction_id' => $transactionId, 'node_port' => $nodeIdentity],
            [
                'room_id'       => $roomId,
                'customer_name' => $customerName,
                'status'        => $reason,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]
        );

        $this->logSystem(
            $transactionId,
            'HỦY LỆNH',
            "Nhận ABORT từ Coordinator → Rollback Phòng {$roomId}. Lý do: {$reason}",
            'error'
        );

        return response()->json(['status' => 'SUCCESS']);
    }

    // =========================================================
    // ENDPOINT: HEALTH CHECK
    // =========================================================
    public function health()
    {
        return response()->json([
            'status' => 'online',
            'node'   => config('app.url'),
            'time'   => now()->toDateTimeString(),
        ]);
    }

    // =========================================================
    // ENDPOINT: FORCE SYNC (Đồng bộ bổ sung khi node vừa wake-up)
    // =========================================================
    public function forceSync(Request $request)
    {
        $bookings = $request->input('bookings', []);

        foreach ($bookings as $data) {
            $nodeIdentity = request()->getHost();
            DB::table('node_bookings')->updateOrInsert(
                ['transaction_id' => $data['transaction_id'], 'node_port' => $nodeIdentity],
                [
                    'room_id'       => $data['room_id']       ?? '',
                    'customer_name' => $data['customer_name'] ?? '',
                    'status'        => $data['status']        ?? 'committed',
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]
            );
        }

        if (count($bookings) > 0) {
            $this->logSystem(
                'SYNC',
                'ĐỒNG BỘ BÙ',
                'Node vừa thức dậy → Nhận ' . count($bookings) . ' giao dịch đồng bộ bù từ Coordinator',
                'info'
            );
        }

        return response()->json(['status' => 'SUCCESS', 'synced' => count($bookings)]);
    }

    // =========================================================
    // HELPER: GHI NHẬT KÝ VÀO BẢNG node_logs
    // =========================================================
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