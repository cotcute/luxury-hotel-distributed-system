<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Mews\Captcha\Facades\Captcha;
use Carbon\Carbon;

class BookingController extends Controller
{
    // TỪ ĐIỂN 22 PHÒNG VẬT LÝ KHÁCH SẠN
    private $roomInstances = [
        'Deluxe Ocean View'   => [101, 102, 103, 104, 105, 106, 107, 108, 109, 110],
        'Royal Executive'     => [201, 202, 203, 204, 205, 206],
        'Signature Penthouse' => [301, 302, 303, 304],
        'Presidential Villa'  => [401, 402]
    ];

    // 1. Hiển thị Form + danh sách phòng còn trống
    public function create(Request $request)
    {
        $roomName  = $request->query('room_name', 'Deluxe Ocean View');
        $roomPrice = $request->query('price', 0);
        $roomImg   = $request->query('img', 'https://images.unsplash.com/photo-1611892440504-42a792e24d32?w=800');

        $allRooms = $this->roomInstances[$roomName] ?? [];

        $bookedRooms = Booking::whereIn('status', ['pending', 'confirmed'])
                              ->pluck('room_id')
                              ->toArray();

        $availableRooms = array_diff($allRooms, $bookedRooms);

        return view('bookings.create', compact('roomName', 'roomPrice', 'roomImg', 'availableRooms'));
    }

    // 2. GỬI REQUEST ĐẾN SERVER NODE
    public function store(Request $request)
    {
        // 1. Validate
        $request->validate([
            'name'        => 'required|string|max:255',
            'phone'       => 'required|string',
            'email'       => 'required|email',
            'room_id'     => 'required',
            'target_node' => 'required',
            'checkin'     => 'required|date',
            'checkout'    => 'required|date',
        ]);

        // 2. Lấy node user chọn
        $targetNodeUrl = $request->input('target_node');

        // 3. Chuẩn bị data gửi đi
        $bookingData = [
            'id'       => uniqid('txn_'),
            'room_id'  => $request->input('room_id'),
            'name'     => $request->input('name'),
            'email'    => $request->input('email'),
            'phone'    => $request->input('phone'),
            'checkin'  => $request->input('checkin'),
            'checkout' => $request->input('checkout'),
        ];

        try {
            // 🚀 Gửi request với timeout 30s
            $response = Http::withoutVerifying()
                ->timeout(30)
                ->post($targetNodeUrl . '/api/client-book', $bookingData);

            // ✅ SUCCESS
            if ($response->ok() && $response->json('status') === 'success') {

                $deadNodes = $response->json('dead_nodes', []);

                if (count($deadNodes) > 0) {
                    $deadNames = implode(', ', $deadNodes);

                    $warningMsg = "Đặt phòng thành công! Dù [ $deadNames ] đang tắt, hệ thống vẫn đạt đủ Quorum (Quá bán) và Commit dữ liệu an toàn lên các máy còn lại.";

                    return redirect()->route('home')->with('success', $warningMsg);
                }

                return redirect()->route('home')->with(
                    'success',
                    'Tuyệt vời! Đặt phòng thành công và dữ liệu đã đồng bộ 100% lên 5 Server.'
                );
            }

            // ❗ BẮT LỖI RENDER (HTML trả về thay vì JSON)
            if (strpos($response->header('Content-Type'), 'text/html') !== false) {
                return back()->with(
                    'error',
                    'Server bạn chọn đang ngủ đông hoặc quá tải. Vui lòng thử lại sau 15 giây hoặc chọn Server khác!'
                )->withInput();
            }

            // ❌ LỖI LOGIC TỪ NODE
            $errorMsg = $response->json('message') ?? 'Lỗi xử lý logic từ Server Node. Mã lỗi: ' . $response->status();

            return back()->with('error', $errorMsg)->withInput();

        } catch (\Exception $e) {
            // ❌ MẤT KẾT NỐI / TIMEOUT
            return back()->with(
                'error',
                'Sập mạng hoàn toàn: Server bạn chọn không thể kết nối!'
            )->withInput();
        }
    }

    // Refresh captcha
    public function refreshCaptcha()
    {
        return response()->json([
            'captcha' => Captcha::img('flat')
        ]);
    }
}