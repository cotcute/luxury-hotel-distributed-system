<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use Illuminate\Support\Facades\Auth;
use Mews\Captcha\Facades\Captcha;
use Carbon\Carbon; 
use App\Services\FourPhaseCommitService;

class BookingController extends Controller
{
    protected $fourPCService;

    // TỪ ĐIỂN 22 PHÒNG VẬT LÝ KHÁCH SẠN
    private $roomInstances = [
        'Deluxe Ocean View'   => [101, 102, 103, 104, 105, 106, 107, 108, 109, 110], // 10 phòng
        'Royal Executive'     => [201, 202, 203, 204, 205, 206],                     // 6 phòng
        'Signature Penthouse' => [301, 302, 303, 304],                               // 4 phòng
        'Presidential Villa'  => [401, 402]                                          // 2 phòng
    ];

    public function __construct(FourPhaseCommitService $fourPCService)
    {
        $this->fourPCService = $fourPCService;
    }

    // 1. Hiển thị Form kèm danh sách phòng CÒN TRỐNG
    public function create(Request $request)
    {
        $roomName  = $request->query('room_name', 'Deluxe Ocean View');
        $roomPrice = $request->query('price', 0);
        $roomImg   = $request->query('img', 'https://images.unsplash.com/photo-1611892440504-42a792e24d32?w=800');

        // Lấy toàn bộ ID phòng vật lý của Loại phòng khách chọn
        $allRooms = $this->roomInstances[$roomName] ?? [];

        // Quét trong Database xem những phòng vật lý nào đang bị Khóa (pending) hoặc Đã bán (confirmed)
        $bookedRooms = Booking::whereIn('status', ['pending', 'confirmed'])
                              ->pluck('room_id')
                              ->toArray();

        // Lọc ra danh sách phòng cụ thể CÒN TRỐNG để đẩy ra giao diện
        $availableRooms = array_diff($allRooms, $bookedRooms);

        return view('bookings.create', compact('roomName', 'roomPrice', 'roomImg', 'availableRooms'));
    }

    // 2. Xử lý Lưu kết hợp luồng 4 Pha
    public function store(Request $request)
    {
        // 1. Validate dữ liệu gốc của bạn
        $request->validate([
            'name'        => 'required|string|max:255',
            'phone'       => 'required|string',
            'email'       => 'required|email',
            'checkin'     => 'required|date|after_or_equal:today',
            'checkout'    => 'required|date|after:checkin',
            'country'     => 'required', 
        ], [
            'checkout.after'  => 'Ngày trả phòng phải sau ngày nhận.',
        ]);

        // =========================================================
        // 2. CHỐT CHẶN KÉP (FRONT-DOOR GUARD) - CHỐNG DOUBLE BOOKING
        // Kiểm tra ngay tại Trung tâm xem phòng này có ai đang giữ không
        // =========================================================
        if ($request->has('room_id')) {
            $isRoomBusy = Booking::where('room_id', $request->room_id)
                                 ->whereIn('status', ['pending', 'confirmed', 'checked_in'])
                                 ->exists();

            if ($isRoomBusy) {
                return back()->with('error', 'Rất tiếc! Phòng số ' . $request->room_id . ' đang có khách hàng khác đang làm thủ tục đặt (PENDING). Vui lòng chọn phòng khác!')->withInput();
            }
        }
        // =========================================================

        try {
            $booking = new Booking();

            // --- GÁN DỮ LIỆU LOGIC GỐC CỦA BẠN ---
            $booking->user_id     = Auth::id();
            $booking->name        = $request->name;       
            $booking->email       = $request->email;
            $booking->phone       = $request->phone;
            $booking->nationality = $request->input('country', 'Vietnam'); 

            if ($request->has('room_id')) {
                $booking->room_id = $request->room_id;
            }
            
            // Xử lý ngày tháng
            $checkInDate  = Carbon::parse($request->checkin);
            $checkOutDate = Carbon::parse($request->checkout);
            $realNights = max(1, $checkInDate->diffInDays($checkOutDate));

            $booking->check_in  = $request->checkin;      
            $booking->check_out = $request->checkout; 
            $booking->total_nights = $realNights;
            $booking->total_price = $request->input('real_price') * $realNights;
            $booking->note        = "Phòng: " . ($request->room_name ?? 'Không rõ') . " | " . $request->note;
            
            $booking->status      = 'pending';
            $booking->save();

            // --- KÍCH HOẠT HỆ PHÂN TÁN 4 PHA ---
            $isSuccess = $this->fourPCService->executeTransaction($booking->toArray());

            if ($isSuccess) {
                $booking->update(['status' => 'confirmed']);
                return redirect()->route('home')->with('success', 'Đặt phòng thành công! Dữ liệu đã đồng bộ lên 5 Server Node.');
            } else {
                $booking->update(['status' => 'cancelled']);
                return back()->with('error', 'Lỗi đồng bộ: Một số máy chủ bận hoặc từ chối kết nối. Đơn đã hủy!')->withInput();
            }

        } catch (\Exception $e) {
            // Nếu 4 Pha ném ra lỗi (bị chiếm phòng hoặc rớt Node), ta cập nhật Hủy luôn đơn này
            if (isset($booking) && $booking->id) {
                $booking->update(['status' => 'cancelled']);
            }
            // In đúng câu lỗi cụ thể ra màn hình
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function refreshCaptcha()
    {
        return response()->json(['captcha' => Captcha::img('flat')]);
    }
}