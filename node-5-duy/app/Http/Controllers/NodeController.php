<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NodeController extends Controller
{
    public function receiveFromClient(Request $request)
    {
        $service = new \App\Services\FourPhaseCommitService();
        try {
            $result = $service->executeTransaction($request->all());
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    public function canCommit(Request $request)
    {
        $rawId = $request->input('id');
        $transactionId = is_string($rawId) && str_starts_with($rawId, 'txn_') ? crc32($rawId) : (int)$rawId;
        $roomId        = $request->input('room_id');
        $isRoomLocked  = DB::table('node_bookings')
            ->where('room_id', $roomId)
            ->where('transaction_id', '!=', $transactionId)
            ->where('status', 'pending')
            ->where('created_at', '>=', now()->subMinutes(5))
            ->exists();
        return response()->json(['status' => $isRoomLocked ? 'NO' : 'YES']);
    }

    public function preCommit(Request $request)
    {
        $rawId = $request->input('transaction_id');
        $transactionId = is_string($rawId) && str_starts_with($rawId, 'txn_') ? crc32($rawId) : (int)$rawId;
        $roomId        = $request->input('room_id');
        $customerName  = $request->input('customer_name', '');
        $nodeIdentity  = rtrim(config('app.url'), '/') ?: gethostname();
        try {
            DB::table('node_bookings')->updateOrInsert(
                ['transaction_id' => $transactionId, 'node_port' => $nodeIdentity],
                ['room_id' => $roomId, 'customer_name' => $customerName, 'status' => 'pending', 'created_at' => now(), 'updated_at' => now()]
            );
            return response()->json(['status' => 'ACK']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'FAILED', 'error' => $e->getMessage()]);
        }
    }

    public function doCommit(Request $request)
    {
        $rawId = $request->input('transaction_id');
        $transactionId = is_string($rawId) && str_starts_with($rawId, 'txn_') ? crc32($rawId) : (int)$rawId;
        DB::table('node_bookings')->updateOrInsert(
            ['transaction_id' => $transactionId, 'node_port' => rtrim(config('app.url'), '/') ?: gethostname()],
            [
                'room_id' => $request->input('room_id'),
                'customer_name' => $request->input('customer_name'),
                'status' => 'committed', 
                'updated_at' => now()
            ]
        );
        return response()->json(['status' => 'SUCCESS']);
    }

    public function abort(Request $request)
    {
        $rawId = $request->input('transaction_id');
        $transactionId = is_string($rawId) && str_starts_with($rawId, 'txn_') ? crc32($rawId) : (int)$rawId;
        $reason        = $request->input('reason', 'ABORTED');
        $roomId        = $request->input('room_id', '');
        $customerName  = $request->input('customer_name', '');
        $nodeIdentity  = rtrim(config('app.url'), '/') ?: gethostname();
        DB::table('node_bookings')->updateOrInsert(
            ['transaction_id' => $transactionId, 'node_port' => $nodeIdentity],
            ['room_id' => $roomId, 'customer_name' => $customerName, 'status' => $reason, 'created_at' => now(), 'updated_at' => now()]
        );
        return response()->json(['status' => 'SUCCESS']);
    }

    public function health()
    {
        return response()->json(['status' => 'online', 'node' => config('app.url'), 'time' => now()->toDateTimeString()]);
    }

    public function forceSync(Request $request)
    {
        $bookings = $request->input('bookings', []);
        $nodeIdentity = rtrim(config('app.url'), '/') ?: gethostname();
        foreach ($bookings as $data) {
            DB::table('node_bookings')->updateOrInsert(
                ['transaction_id' => $data['transaction_id'], 'node_port' => $nodeIdentity],
                ['room_id' => $data['room_id'] ?? '', 'customer_name' => $data['customer_name'] ?? '', 'status' => $data['status'] ?? 'committed', 'created_at' => now(), 'updated_at' => now()]
            );
        }
        return response()->json(['status' => 'SUCCESS', 'synced' => count($bookings)]);
    }
}