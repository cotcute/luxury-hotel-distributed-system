<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

Route::get('/', function () {
    $port = request()->getHost();

    // Lấy lịch sử giao dịch của đúng cái Host này
    $transactions = DB::table('node_bookings')
        ->where('node_port', $port)
        ->orderBy('updated_at', 'desc')
        ->take(10)
        ->get();

    // Lấy nhật ký 4PC của node này
    $logs = DB::table('node_logs')
        ->where('node_port', $port)
        ->orderBy('created_at', 'desc')
        ->take(30)
        ->get()
        ->reverse()
        ->values();

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