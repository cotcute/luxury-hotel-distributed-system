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

        // 3. Chuẩn bị data gửi đi (Dùng INT tĩnh thay vì chuỗi txn_ để chống lỗi SQL 1366)
        $bookingData = [
            'id'       => time() . random_int(100, 999),
            'room_id'  => $request->input('room_id'),
            'name'     => $request->input('name'),
            'email'    => $request->input('email'),
            'phone'    => $request->input('phone'),
            'checkin'  => $request->input('checkin'),
            'checkout' => $request->input('checkout'),
        ];

        try {
            // 🚀 Gửi request lần 1 với timeout 60s
            $response = Http::withoutVerifying()
                ->timeout(60)
                ->post($targetNodeUrl . '/api/client-book', $bookingData);

            // ❗ Render cold-start: trả HTML thay vì JSON → chờ 35s và RETRY
            if (strpos($response->header('Content-Type') ?? '', 'text/html') !== false) {
                set_time_limit(180);
                sleep(35);
                $response = Http::withoutVerifying()
                    ->timeout(90)
                    ->post($targetNodeUrl . '/api/client-book', $bookingData);
            }

            // ✅ SUCCESS
            if ($response->ok() && $response->json('status') === 'success') {

                // 💾 Ghi lại booking vào DB của Center Server
                $checkin  = Carbon::parse($request->input('checkin'));
                $checkout = Carbon::parse($request->input('checkout'));
                $nights   = max(1, $checkin->diffInDays($checkout));
                $roomName = $request->input('room_name', 'N/A');
                $price    = (float) $request->input('real_price', 0);

                Booking::create([
                    'user_id'     => Auth::id(),
                    'room_id'     => $request->input('room_id'),
                    'name'        => $request->input('name'),
                    'email'       => $request->input('email'),
                    'phone'       => $request->input('phone'),
                    'nationality' => $request->input('country', 'VN'),
                    'checkin'     => $checkin,
                    'checkout'    => $checkout,
                    'check_in'    => $checkin,
                    'check_out'   => $checkout,
                    'total_nights'=> $nights,
                    'total_price' => $price * $nights,
                    'status'      => 'confirmed',
                    'note'        => 'Phòng: ' . $roomName . ' | Transaction: ' . $bookingData['id'],
                ]);

                $deadNodes = $response->json('dead_nodes', []);

                if (count($deadNodes) > 0) {
                    $deadNames = implode(', ', $deadNodes);
                    $warningMsg = "Đặt phòng thành công! Dù [ $deadNames ] đang tắt, hệ thống vẫn đạt đủ Quorum và Commit dữ liệu an toàn lên các máy còn lại.";
                    return redirect()->route('home')->with('success', $warningMsg);
                }

                return redirect()->route('home')->with(
                    'success',
                    'Tuyệt vời! Đặt phòng thành công và dữ liệu đã đồng bộ 100% lên 5 Server.'
                );
            }

            // ❗ BẮT LỖI RENDER (HTML trả về thay vì JSON - Node đang wake-up)
            if (strpos($response->header('Content-Type'), 'text/html') !== false) {
                return back()->with(
                    'error',
                    'Server Node đang khởi động lại (Render cold start). Vui lòng thử lại sau 20-30 giây hoặc chọn Server khác!'
                )->withInput();
            }

            // ❌ LỖI LOGIC TỪ NODE
            $errorMsg = $response->json('message') ?? 'Lỗi xử lý từ Server Node. Mã lỗi: ' . $response->status();
            return back()->with('error', $errorMsg)->withInput();

        } catch (\Exception $e) {
            // ❌ MẤT KẾT NỐI / TIMEOUT
            return back()->with(
                'error',
                'Không thể kết nối đến Server Node! Render có thể đang wake-up (mất ~30s). Vui lòng thử lại!'
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