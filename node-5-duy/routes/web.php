<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

Route::get('/', function () {
    $port = request()->getHost();

    // Chỉ lấy lịch sử giao dịch của đúng node này
    $transactions = DB::table('node_bookings')
        ->where('node_port', $port)
        ->orderBy('updated_at', 'desc')
        ->take(10)
        ->get();

    return view('welcome', compact('port', 'transactions'));
});