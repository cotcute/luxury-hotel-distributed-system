<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Booking;

class DashboardController extends Controller
{
    public function index()
    {
        // Lấy danh sách đơn đặt phòng của user đang đăng nhập
        // Sắp xếp đơn mới nhất lên đầu
        $bookings = Booking::where('user_id', Auth::id())
                            ->orderBy('created_at', 'desc')
                            ->get();

        return view('dashboard.index', compact('bookings'));
    }
}