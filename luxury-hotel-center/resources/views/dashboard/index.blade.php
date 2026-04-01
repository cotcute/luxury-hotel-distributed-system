@extends('layouts.app')

@section('title', 'Lịch Sử Đặt Phòng - Luxury Hotel')

@section('content')
<!-- Banner Header -->
<div class="bg-dark py-5 text-white text-center" style="margin-top: 70px;">
    <h2 class="font-playfair display-5">Quản Lý Tài Khoản</h2>
    <p class="lead mb-0">Xin chào, {{ Auth::user()->name }}</p>
</div>

<div class="container my-5">
    <div class="row">
        <!-- Sidebar Menu -->
        <div class="col-lg-3 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <a href="#"
                            class="list-group-item list-group-item-action active bg-warning border-0 text-dark fw-bold">
                            <i class="fa-solid fa-list-check me-2"></i> Lịch sử đặt phòng
                        </a>
                        <a href="#" class="list-group-item list-group-item-action">
                            <i class="fa-solid fa-user-pen me-2"></i> Thông tin cá nhân
                        </a>
                        <a href="#" class="list-group-item list-group-item-action">
                            <i class="fa-solid fa-key me-2"></i> Đổi mật khẩu
                        </a>
                        <a href="#" class="list-group-item list-group-item-action text-danger"
                            onclick="event.preventDefault(); document.getElementById('logout-form-dash').submit();">
                            <i class="fa-solid fa-right-from-bracket me-2"></i> Đăng xuất
                        </a>
                        <form id="logout-form-dash" action="{{ route('logout') }}" method="POST" class="d-none">@csrf
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content: Danh sách đơn hàng -->
        <div class="col-lg-9">
            <h4 class="font-playfair mb-4 border-bottom pb-2">Lịch Sử Các Chuyến Đi</h4>

            @if($bookings->count() > 0)
            <div class="table-responsive shadow-sm rounded">
                <table class="table table-hover align-middle mb-0 bg-white">
                    <thead class="bg-light">
                        <tr>
                            <th class="py-3 ps-4">Mã đơn</th>
                            <th class="py-3">Thông tin phòng</th>
                            <th class="py-3">Thời gian</th>
                            <th class="py-3">Tổng tiền</th>
                            <th class="py-3">Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($bookings as $booking)
                        <tr>
                            <td class="ps-4 fw-bold text-muted">#{{ $booking->id }}</td>
                            <td>
                                <!-- Lấy tên phòng từ Note (cắt chuỗi đơn giản để demo) -->
                                <span class="fw-bold d-block text-dark">
                                    {{ Str::limit($booking->note, 30) }}
                                </span>
                                <small class="text-muted">{{ $booking->name }}</small>
                            </td>
                            <td>
                                <div class="small">
                                    <div class="text-success"><i class="fa-solid fa-calendar-check me-1"></i>
                                        {{ \Carbon\Carbon::parse($booking->checkin)->format('d/m/Y') }}</div>
                                    <div class="text-danger"><i class="fa-solid fa-calendar-xmark me-1"></i>
                                        {{ \Carbon\Carbon::parse($booking->checkout)->format('d/m/Y') }}</div>
                                </div>
                            </td>
                            <td class="fw-bold text-warning">
                                {{ number_format($booking->total_price, 0, ',', '.') }} ₫
                            </td>
                            <td>
                                @if($booking->status == 'pending')
                                <span class="badge bg-warning text-dark">Chờ duyệt</span>
                                @elseif($booking->status == 'confirmed')
                                <span class="badge bg-success">Đã xác nhận</span>
                                @else
                                <span class="badge bg-secondary">Đã hủy</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="text-center py-5 bg-light rounded">
                <i class="fa-regular fa-calendar-xmark fa-4x text-muted mb-3"></i>
                <p class="text-muted">Bạn chưa có đơn đặt phòng nào.</p>
                <a href="{{ route('home') }}" class="btn btn-luxury">Đặt phòng ngay</a>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection