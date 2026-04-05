<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

Route::get('/', function () {
    $port = request()->getHost();

    // Lấy lịch sử giao dịch của node này
    try {
        $transactions = DB::table('node_bookings')
            ->where('node_port', $port)
            ->orderBy('updated_at', 'desc')
            ->take(10)
            ->get();
    } catch (\Exception $e) {
        $transactions = collect([]);
    }

    // Lấy nhật ký 4PC của node này
    try {
        $logs = DB::table('node_logs')
            ->where('node_port', $port)
            ->orderBy('created_at', 'desc')
            ->take(30)
            ->get()
            ->reverse()
            ->values();
    } catch (\Exception $e) {
        $logs = collect([]);
    }

    return view('welcome', compact('port', 'transactions', 'logs'));
});

Route::get('/run-migrations', function () {
    try {
        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        return response()->json(['message' => 'Migrate thành công! Bảng node_logs đã sẵn sàng.']);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()]);
    }
});