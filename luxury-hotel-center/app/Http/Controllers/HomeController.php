<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Contact;
use App\Models\Booking; // Nhớ thêm dòng này để đếm đơn

class HomeController extends Controller
{
    /**
     * 1. HIỂN THỊ TRANG CHỦ (Đã nâng cấp tính năng đếm phòng)
     */
    public function index()
    {
        // 1. Cấu hình danh sách phòng và Tổng số lượng (Quota) tại đây
        // Bạn có thể sửa số 'total' theo ý muốn
        $rooms = [
            [
                'name' => 'Deluxe Ocean View',
                'price' => 1350000,
                'old_price' => 1500000,
                'img' => 'https://images.unsplash.com/photo-1611892440504-42a792e24d32?w=800',
                'discount' => '-10%',
                'total' => 10 // Tổng có 10 phòng
            ],
            [
                'name' => 'Royal Executive',
                'price' => 2375000,
                'old_price' => 2500000,
                'img' => 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?w=800',
                'discount' => '-5%',
                'total' => 6 // Tổng có 6 phòng
            ],
            [
                'name' => 'Signature Penthouse',
                'price' => 4250000,
                'old_price' => 5000000,
                'img' => 'https://images.unsplash.com/photo-1591088398332-8a7791972843?w=800',
                'discount' => '-15%',
                'total' => 4 // Tổng có 4 phòng
            ],
            [
                'name' => 'Presidential Villa',
                'price' => 10000000,
                'old_price' => null, // Không giảm giá
                'img' => 'https://images.unsplash.com/photo-1602002418082-a4443e081dd1?w=800',
                'discount' => 'VIP',
                'total' => 2 // Hàng hiếm, chỉ có 2 phòng
            ]
        ];

        // 2. Vòng lặp tính toán số phòng còn trống
        foreach ($rooms as $key => $room) {
            // Đếm số đơn đặt phòng có tên phòng này VÀ chưa bị hủy
            $bookedCount = Booking::where('note', 'LIKE', '%Phòng: ' . $room['name'] . '%')
                                  ->whereIn('status', ['pending', 'confirmed']) // Chỉ tính đơn đang hoạt động
                                  ->count();

            // Tính số còn lại (Không được nhỏ hơn 0)
            $available = max(0, $room['total'] - $bookedCount);
            
            // Gán ngược lại vào mảng để đưa sang View
            $rooms[$key]['available'] = $available;
        }

        return view('home', compact('rooms')); 
    }

    /**
     * 2. XỬ LÝ GỬI LIÊN HỆ
     */
    public function sendContact(Request $request) 
    {
        $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'required|email|max:255',
            'message' => 'required|string',
        ], [
            'name.required'    => 'Vui lòng nhập họ tên của bạn.',
            'email.required'   => 'Vui lòng nhập địa chỉ email.',
            'message.required' => 'Vui lòng nhập nội dung tin nhắn.',
        ]);

        Contact::create([
            'name'    => $request->name,
            'email'   => $request->email,
            'message' => $request->message,
        ]);

        return redirect()->back()->with('success', 'Cảm ơn bạn đã liên hệ! Chúng tôi sẽ phản hồi sớm nhất.');
    }
}