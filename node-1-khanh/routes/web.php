<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

Route::get('/', function () {
    $port = request()->getHost();
    
    // Chỉ lấy lịch sử giao dịch của đúng cái Host này
    $transactions = DB::table('node_bookings')
        ->whereIn('node_port', [$port, 'node-1-khanh.onrender.com'])
        ->orderBy('updated_at', 'desc')
        ->take(10)
        ->get();

    return view('welcome', compact('port', 'transactions'));
});