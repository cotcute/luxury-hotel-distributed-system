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
        $roomId = $request->input('room_id');
        
        // BÍ KÍP GIẢI QUYẾT LỖI CƯỚP PHÒNG ẢO:
        // 1. KHÔNG check 'committed' nữa. Đầu não đã chặn trùng ngày ở cửa ngoài rồi.
        // 2. Chống kẹt rác: Chỉ block những giao dịch 'pending' mới tạo trong 2 PHÚT gần nhất!
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

    // PHA 2: Chuẩn bị 
    public function preCommit(Request $request)
    {
        $transactionId = $request->input('transaction_id');
        $roomId = $request->input('room_id');
        $customerName = $request->input('customer_name');
        
        // Render hay sinh ra port ảo, bỏ qua luôn, dùng transaction_id làm khóa chính là đủ!
        $nodePort = $request->server('SERVER_PORT') ?? 80; 

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

        // Bỏ kiểm tra node_port để tránh Render đổi cổng gây mất kết nối
        $updated = DB::table('node_bookings')
            ->where('transaction_id', $transactionId)
            ->where('status', 'pending')
            ->update(['status' => 'committed', 'updated_at' => now()]);

        if ($updated) {
            return response()->json(['status' => 'SUCCESS']);
        }
        return response()->json(['status' => 'FAILED']);
    }

    // PHA 4: Hủy bỏ
    public function abort(Request $request)
    {
        $transactionId = $request->input('transaction_id');
        $reason = $request->input('reason', 'ABORTED');
        $roomId = $request->input('room_id');
        $customerName = $request->input('customer_name');
        $nodePort = $request->server('SERVER_PORT') ?? 80; 

        $exists = DB::table('node_bookings')
            ->where('transaction_id', $transactionId)
            ->exists();

        if ($exists) {
            DB::table('node_bookings')
                ->where('transaction_id', $transactionId)
                ->update(['status' => $reason, 'updated_at' => now()]);
        } else {
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