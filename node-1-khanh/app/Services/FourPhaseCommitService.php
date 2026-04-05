<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NodeController extends Controller
{
    // Giao tiếp với CLIENT: Đứng ra làm Nhạc trưởng
    public function receiveFromClient(Request $request)
    {
        $service = new \App\Services\FourPhaseCommitService();
        try {
            $result = $service->executeTransaction($request->all());
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error', 
                'message' => $e->getMessage()
            ], 400); 
        }
    }

    // LẤY DANH TÍNH CỦA MÁY (Tránh giẫm đạp DB chung)
    private function getNodeIdentity(Request $request) {
        return $request->getHost() ?? 'Unknown_Node';
    }

    // PHA 1: Voting (Kiểm tra phòng)
    public function canCommit(Request $request)
    {
        $transactionId = $request->input('id'); 
        $roomId = $request->input('room_id');
        
        $isRoomLocked = DB::table('node_bookings')
            ->where('room_id', $roomId)
            ->where('transaction_id', '!=', $transactionId)
            ->where('status', 'pending')
            ->where('created_at', '>=', now()->subMinutes(2)) 
            ->exists();
        
        if ($isRoomLocked) {
            return response()->json(['status' => 'NO']);
        }

        return response()->json(['status' => 'YES']);
    }

    // PHA 2: Chuẩn bị (Reserve) - VŨ KHÍ CHỐNG CRASH TẠI ĐÂY
    public function preCommit(Request $request)
    {
        $transactionId = $request->input('transaction_id');
        $roomId = $request->input('room_id');
        $customerName = $request->input('customer_name');
        $nodeIdentity = $this->getNodeIdentity($request);

        try {
            // DÙNG updateOrInsert: 5 máy cùng ập vào 1 DB cũng không bao giờ bị Crash SQL!
            DB::table('node_bookings')->updateOrInsert(
                [
                    'transaction_id' => $transactionId,
                    'node_port'      => $nodeIdentity // Định danh riêng biệt từng máy
                ],
                [
                    'room_id'        => $roomId,
                    'customer_name'  => $customerName,
                    'status'         => 'pending',
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]
            );
            return response()->json(['status' => 'ACK']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'FAILED', 'error' => $e->getMessage()]);
        }
    }

    // PHA 3: Chốt hạ (Commit)
    public function doCommit(Request $request)
    {
        $transactionId = $request->input('transaction_id');
        $nodeIdentity = $this->getNodeIdentity($request);

        $updated = DB::table('node_bookings')
            ->where('transaction_id', $transactionId)
            ->where('node_port', $nodeIdentity) // Chỉ Update dòng của chính mình
            ->where('status', 'pending')
            ->update(['status' => 'committed', 'updated_at' => now()]);

        return response()->json(['status' => 'SUCCESS']);
    }

    // PHA 4: Hủy bỏ (Abort)
    public function abort(Request $request)
    {
        $transactionId = $request->input('transaction_id');
        $reason = $request->input('reason', 'ABORTED');
        $roomId = $request->input('room_id');
        $customerName = $request->input('customer_name');
        $nodeIdentity = $this->getNodeIdentity($request);

        // Chống Crash khi dọn rác
        DB::table('node_bookings')->updateOrInsert(
            [
                'transaction_id' => $transactionId,
                'node_port'      => $nodeIdentity
            ],
            [
                'room_id'        => $roomId,
                'customer_name'  => $customerName,
                'status'         => $reason,
                'updated_at'     => now(),
            ]
        );

        return response()->json(['status' => 'SUCCESS']);
    }
}