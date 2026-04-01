<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\Contact;
use App\Models\User;

class AdminController extends Controller
{
    // 1. Dashboard Chính
    public function index()
    {
        $totalBookings = Booking::count();
        // Doanh thu chỉ tính đơn đã hoàn thành hoặc đang ở
        $totalRevenue = Booking::whereIn('status', ['confirmed', 'checked_in', 'checked_out'])->sum('total_price'); 
        $newMessages = Contact::count();
        $totalUsers = User::where('role', 'user')->count();
        $recentBookings = Booking::orderBy('created_at', 'desc')->take(5)->get();

        // --- LOGIC TỒN KHO PHÒNG CHUẨN XÁC 100% THEO ID VẬT LÝ ---
        $roomInstances = [
            'Deluxe Ocean View'   => [101, 102, 103, 104, 105, 106, 107, 108, 109, 110], // 10 phòng
            'Royal Executive'     => [201, 202, 203, 204, 205, 206],                     // 6 phòng
            'Signature Penthouse' => [301, 302, 303, 304],                               // 4 phòng
            'Presidential Villa'  => [401, 402]                                          // 2 phòng
        ];

        $roomStatus = [];

        foreach ($roomInstances as $roomName => $ids) {
            $total = count($ids);
            
            // QUAN TRỌNG: Đếm cả 'pending', 'confirmed' VÀ 'checked_in'
            // Tìm chính xác theo Mảng ID Phòng (Ví dụ: [301, 302, 303, 304])
            // Lọc ra các phòng đang bị khóa bởi 4 Pha hoặc đã được đặt
            $booked = Booking::whereIn('room_id', $ids)
                             ->whereIn('status', ['pending', 'confirmed', 'checked_in']) 
                             ->count();
            
            $available = max(0, $total - $booked);
            $percent = ($total > 0) ? round(($booked / $total) * 100) : 0;

            $roomStatus[] = [
                'name'      => $roomName,
                'total'     => $total,
                'booked'    => $booked,
                'available' => $available,
                'percent'   => $percent
            ];
        }

        return view('admin.dashboard', compact(
            'totalBookings', 'totalRevenue', 'newMessages', 'totalUsers', 'recentBookings', 'roomStatus'
        ));
    }

    // 2. Danh sách đơn
    public function bookings()
    {
        $bookings = Booking::orderBy('created_at', 'desc')->paginate(10);
        return view('admin.bookings.index', compact('bookings'));
    }

    // 3. Cập nhật trạng thái (Check-in / Check-out)
    public function updateBookingStatus(Request $request, $id)
    {
        $booking = Booking::findOrFail($id);
        
        // Nhận trạng thái từ form (confirmed, checked_in, checked_out, cancelled)
        $booking->status = $request->status; 
        $booking->save();

        // Thông báo tùy theo hành động
        $msg = 'Cập nhật thành công!';
        if ($request->status == 'checked_out') {
            $msg = 'Đã trả phòng thành công! Phòng đã trống cho khách mới.';
        } elseif ($request->status == 'checked_in') {
            $msg = 'Khách đã nhận phòng thành công.';
        }

        return redirect()->back()->with('success', $msg);
    }

    // 4. Tin nhắn
    public function messages()
    {
        $messages = Contact::orderBy('created_at', 'desc')->paginate(10);
        return view('admin.messages.index', compact('messages'));
    }
}