<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NodeController extends Controller
{
    // Lấy NODE_ID: ưu tiên env NODE_ID (production/Render), fallback về SERVER_PORT (local)
    private function getNodeId(Request $request): string
    {
        return env('NODE_ID', $request->server('SERVER_PORT'));
    }

    // PHA 1: Voting (Kiểm tra phòng đã bị ai chiếm chưa)
    public function canCommit(Request $request)
    {
        $transactionId = $request->input('id');
        $roomId        = $request->input('room_id');
        $nodeId        = $this->getNodeId($request);

        // KHÓA PHÒNG: Nếu phòng đang PENDING hoặc COMMITTED -> TỪ CHỐI
        $isRoomLocked = DB::table('node_bookings')
            ->where('node_port', $nodeId)
            ->where('room_id', $roomId)
            ->whereIn('status', ['pending', 'committed'])
            ->exists();

        if ($isRoomLocked) {
            return response()->json(['status' => 'NO']);
        }

        return response()->json(['status' => 'YES']);
    }

    // PHA 2: Chuẩn bị (Lưu Tên và ID phòng vào DB với trạng thái PENDING)
    public function preCommit(Request $request)
    {
        $transactionId = $request->input('transaction_id');
        $roomId        = $request->input('room_id');
        $customerName  = $request->input('customer_name');
        $nodeId        = $this->getNodeId($request);

        try {
            DB::table('node_bookings')->insert([
                'transaction_id' => $transactionId,
                'node_port'      => $nodeId,
                'room_id'        => $roomId,
                'customer_name'  => $customerName,
                'status'         => 'pending',
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
            return response()->json(['status' => 'ACK']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'FAILED', 'error' => $e->getMessage()]);
        }
    }

    // PHA 3: Chốt hạ (đổi PENDING → COMMITTED)
    public function doCommit(Request $request)
    {
        $transactionId = $request->input('transaction_id');
        $nodeId        = $this->getNodeId($request);

        $updated = DB::table('node_bookings')
            ->where('transaction_id', $transactionId)
            ->where('node_port', $nodeId)
            ->where('status', 'pending')
            ->update(['status' => 'committed', 'updated_at' => now()]);

        if ($updated) {
            return response()->json(['status' => 'SUCCESS']);
        }
        return response()->json(['status' => 'FAILED']);
    }

    // PHA 4: Hủy bỏ (abort toàn bộ giao dịch)
    public function abort(Request $request)
    {
        $transactionId = $request->input('transaction_id');
        $nodeId        = $this->getNodeId($request);
        $reason        = $request->input('reason', 'ABORTED');
        $roomId        = $request->input('room_id');
        $customerName  = $request->input('customer_name');

        // Kiểm tra giao dịch đã tồn tại trong Node chưa
        $exists = DB::table('node_bookings')
            ->where('transaction_id', $transactionId)
            ->where('node_port', $nodeId)
            ->exists();

        if ($exists) {
            // Đã tồn tại (ghi ở Pha 2) → chỉ cập nhật status
            DB::table('node_bookings')
                ->where('transaction_id', $transactionId)
                ->where('node_port', $nodeId)
                ->update(['status' => $reason, 'updated_at' => now()]);
        } else {
            // Chưa tồn tại (chết ở Pha 1) → INSERT mới để hiển thị lên màn hình
            DB::table('node_bookings')->insert([
                'transaction_id' => $transactionId,
                'node_port'      => $nodeId,
                'room_id'        => $roomId,
                'customer_name'  => $customerName,
                'status'         => $reason,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }

        return response()->json(['status' => 'SUCCESS']);
    }

    // HEALTH CHECK: Server tổng ping để kiểm tra node còn sống không
    public function health(Request $request)
    {
        return response()->json([
            'status'  => 'OK',
            'node_id' => $this->getNodeId($request),
            'time'    => now()->toIso8601String(),
        ]);
    }
}