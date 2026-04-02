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
            // 🔥 Node này sẽ đứng ra điều phối toàn bộ 4PC
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
    // PHA 1: Voting (Kiểm tra phòng có bị giữ chưa)
    // =========================================================
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

    // =========================================================
    // PHA 2: PRE-COMMIT (Ghi tạm)
    // =========================================================
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

    // =========================================================
    // PHA 3: COMMIT
    // =========================================================
    public function doCommit(Request $request)
    {
        $transactionId = $request->input('transaction_id');

        $updated = DB::table('node_bookings')
            ->where('transaction_id', $transactionId)
            ->where('status', 'pending')
            ->update([
                'status' => 'committed',
                'updated_at' => now()
            ]);

        if ($updated) {
            return response()->json(['status' => 'SUCCESS']);
        }

        return response()->json(['status' => 'FAILED']);
    }

    // =========================================================
    // PHA 4: ABORT (Rollback)
    // =========================================================
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
                ->update([
                    'status' => $reason,
                    'updated_at' => now()
                ]);
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
    // 🔥 FORCE SYNC (ĐỒNG BỘ BÙ)
    // =========================================================
    public function forceSync(Request $request)
    {
        $bookings = $request->input('bookings', []);

        foreach ($bookings as $data) {
            $exists = DB::table('node_bookings')
                ->where('transaction_id', $data['transaction_id'])
                ->exists();

            if ($exists) {
                DB::table('node_bookings')
                    ->where('transaction_id', $data['transaction_id'])
                    ->update([
                        'status' => $data['status'],
                        'updated_at' => now()
                    ]);
            } else {
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