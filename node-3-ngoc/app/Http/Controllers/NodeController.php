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
        
        // Chống kẹt rác: Chỉ block những giao dịch 'pending' mới tạo trong 2 PHÚT gần nhất!
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

    // =========================================================
    // VŨ KHÍ TỐI THƯỢNG: CỔNG NHẬN DỮ LIỆU ĐỒNG BỘ BÙ (MANUAL SYNC)
    // =========================================================
    public function forceSync(Request $request)
    {
        $bookings = $request->input('bookings', []);

        foreach ($bookings as $data) {
            $exists = DB::table('node_bookings')
                ->where('transaction_id', $data['transaction_id'])
                ->exists();

            if ($exists) {
                // Đã có đơn -> Cập nhật lại cho chắc chắn là 'committed'
                DB::table('node_bookings')
                    ->where('transaction_id', $data['transaction_id'])
                    ->update([
                        'status' => $data['status'],
                        'updated_at' => now()
                    ]);
            } else {
                // Chưa có (do sập nguồn) -> Chèn mới vào!
                DB::table('node_bookings')->insert([
                    'transaction_id' => $data['transaction_id'],
                    'node_port'      => 80,
                    'room_id'        => $data['room_id'],
                    'customer_name'  => $data['customer_name'],
                    'status'         => $data['status'],
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
            }
        }

        return response()->json(['status' => 'SUCCESS']);
    }
}