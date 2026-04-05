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
    // ENDPOINT: CAN-COMMIT (Kiểm tra phòng có bị lock không)
    // =========================================================
    public function canCommit(Request $request)
    {
        $transactionId = $request->input('id');
        $roomId        = $request->input('room_id');

        $isRoomLocked = DB::table('node_bookings')
            ->where('room_id', $roomId)
            ->where('transaction_id', '!=', $transactionId)
            ->where('status', 'pending')
            ->where('created_at', '>=', now()->subMinutes(5))
            ->exists();

        return response()->json(['status' => $isRoomLocked ? 'NO' : 'YES']);
    }

    // =========================================================
    // ENDPOINT: PRE-COMMIT (Ghi tạm - dùng updateOrInsert chống crash)
    // =========================================================
    public function preCommit(Request $request)
    {
        $transactionId = $request->input('transaction_id');
        $roomId        = $request->input('room_id');
        $customerName  = $request->input('customer_name', '');
        $nodeIdentity  = rtrim(config('app.url'), '/') ?: gethostname();

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
            return response()->json(['status' => 'ACK']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'FAILED', 'error' => $e->getMessage()]);
        }
    }

    // =========================================================
    // ENDPOINT: DO-COMMIT (Chốt giao dịch chính thức)
    // =========================================================
    public function doCommit(Request $request)
    {
        $transactionId = $request->input('transaction_id');

        DB::table('node_bookings')
            ->where('transaction_id', $transactionId)
            ->where('status', 'pending')
            ->update(['status' => 'committed', 'updated_at' => now()]);

        return response()->json(['status' => 'SUCCESS']);
    }

    // =========================================================
    // ENDPOINT: ABORT (Rollback - dọn sạch pending lock)
    // =========================================================
    public function abort(Request $request)
    {
        $transactionId = $request->input('transaction_id');
        $reason        = $request->input('reason', 'ABORTED');
        $roomId        = $request->input('room_id', '');
        $customerName  = $request->input('customer_name', '');
        $nodeIdentity  = rtrim(config('app.url'), '/') ?: gethostname();

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

        return response()->json(['status' => 'SUCCESS']);
    }

    // =========================================================
    // ENDPOINT: HEALTH CHECK (Kiểm tra node có online không)
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
            $nodeIdentity = rtrim(config('app.url'), '/') ?: gethostname();
            DB::table('node_bookings')->updateOrInsert(
                ['transaction_id' => $data['transaction_id'], 'node_port' => $nodeIdentity],
                [
                    'room_id'       => $data['room_id']        ?? '',
                    'customer_name' => $data['customer_name']  ?? '',
                    'status'        => $data['status']         ?? 'committed',
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]
            );
        }

        return response()->json(['status' => 'SUCCESS', 'synced' => count($bookings)]);
    }
}