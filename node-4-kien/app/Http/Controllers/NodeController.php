<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NodeController extends Controller
{
    // PHA 1: Voting (Kiểm tra phòng đã bị ai chiếm chưa)
    public function canCommit(Request $request)
    {
        $transactionId = $request->input('id'); 
        $roomId = $request->input('room_id'); // Lấy ID phòng từ Trung tâm
        $nodePort = $request->server('SERVER_PORT'); 
        
        // KHÓA CỔNG: Nếu ID phòng này đang bị pending hoặc committed bởi NGƯỜI KHÁC -> TỪ CHỐI
        $isRoomLocked = DB::table('node_bookings')
            ->where('node_port', $nodePort)
            ->where('room_id', $roomId)
            ->whereIn('status', ['pending', 'committed'])
            ->exists();
        
        if ($isRoomLocked) {
            return response()->json(['status' => 'NO']);
        }

        return response()->json(['status' => 'YES']);
    }

    // PHA 2: Chuẩn bị (Lưu Tên và ID phòng)
    public function preCommit(Request $request)
    {
        $transactionId = $request->input('transaction_id');
        $roomId = $request->input('room_id');
        $customerName = $request->input('customer_name');
        $nodePort = $request->server('SERVER_PORT'); 

        try {
            DB::table('node_bookings')->insert([
                'transaction_id' => $transactionId,
                'node_port'      => $nodePort,
                'room_id'        => $roomId,
                'customer_name'  => $customerName,
                'status'         => 'pending',
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
            return response()->json(['status' => 'ACK']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'FAILED']);
        }
    }

    // PHA 3: Chốt hạ
    public function doCommit(Request $request)
    {
        $transactionId = $request->input('transaction_id');
        $nodePort = $request->server('SERVER_PORT'); 

        $updated = DB::table('node_bookings')
            ->where('transaction_id', $transactionId)
            ->where('node_port', $nodePort) 
            ->where('status', 'pending')
            ->update(['status' => 'committed', 'updated_at' => now()]);

        if ($updated) {
            return response()->json(['status' => 'SUCCESS']);
        }
        return response()->json(['status' => 'FAILED']);
    }

    // PHA 4: Hủy bỏ
    // PHA 4: Hủy bỏ
    // PHA 4: Hủy bỏ
    public function abort(Request $request)
    {
        $transactionId = $request->input('transaction_id');
        $nodePort = $request->server('SERVER_PORT'); 
        $reason = $request->input('reason', 'ABORTED');
        $roomId = $request->input('room_id');
        $customerName = $request->input('customer_name');

        // BẮT BỆNH: Kiểm tra xem giao dịch đã tồn tại trong Node chưa
        $exists = DB::table('node_bookings')
            ->where('transaction_id', $transactionId)
            ->where('node_port', $nodePort)
            ->exists();

        if ($exists) {
            // Nếu đã tồn tại (Chết ở Pha 2 hoặc 3), thì chỉ Cập nhật
            DB::table('node_bookings')
                ->where('transaction_id', $transactionId)
                ->where('node_port', $nodePort)
                ->update(['status' => $reason, 'updated_at' => now()]);
        } else {
            // Nếu CHƯA tồn tại (Chết ngay Pha 1), thì INSERT MỚI luôn để vẽ lên màn hình
            DB::table('node_bookings')->insert([
                'transaction_id' => $transactionId,
                'node_port'      => $nodePort,
                'room_id'        => $roomId,
                'customer_name'  => $customerName,
                'status'         => $reason,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }

        return response()->json(['status' => 'SUCCESS']);
    }
}