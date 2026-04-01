@extends('layouts.admin')

@section('content')
<h2 class="mb-4 text-gray-800">Tổng Quan Quản Trị</h2>

{{-- 1. THẺ THỐNG KÊ (STATS CARDS) --}}
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card card-stat bg-primary text-white p-3 h-100 shadow-sm">
            <h3>{{ $totalBookings }}</h3>
            <p class="mb-0">Tổng Đơn Đặt</p>
            <i class="fa-solid fa-bookmark position-absolute top-0 end-0 p-3 opacity-25 fa-3x"></i>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-stat bg-success text-white p-3 h-100 shadow-sm">
            <h3>{{ number_format($totalRevenue) }} ₫</h3>
            <p class="mb-0">Doanh Thu (Thực tế)</p>
            <i class="fa-solid fa-money-bill-wave position-absolute top-0 end-0 p-3 opacity-25 fa-3x"></i>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-stat bg-warning text-dark p-3 h-100 shadow-sm">
            <h3>{{ $newMessages }}</h3>
            <p class="mb-0">Tin Nhắn Mới</p>
            <i class="fa-solid fa-envelope position-absolute top-0 end-0 p-3 opacity-25 fa-3x"></i>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-stat bg-info text-white p-3 h-100 shadow-sm">
            <h3>{{ $totalUsers }}</h3>
            <p class="mb-0">Khách Hàng</p>
            <i class="fa-solid fa-users position-absolute top-0 end-0 p-3 opacity-25 fa-3x"></i>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    {{-- 2. TÌNH TRẠNG KHO PHÒNG (MỚI THÊM) --}}
    <div class="col-lg-5">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="m-0 font-weight-bold text-dark"><i class="fa-solid fa-bed me-2"></i> Tình Trạng Kho Phòng
                </h6>
            </div>
            <div class="card-body">
                @foreach($roomStatus as $room)
                <div class="mb-4">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="fw-bold text-dark small">{{ $room['name'] }}</span>
                        <small class="{{ $room['available'] == 0 ? 'text-danger fw-bold' : 'text-success' }}">
                            Còn trống: {{ $room['available'] }} / {{ $room['total'] }}
                        </small>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar {{ $room['percent'] >= 100 ? 'bg-danger' : ($room['percent'] > 50 ? 'bg-warning' : 'bg-success') }}"
                            role="progressbar" style="width: {{ $room['percent'] }}%">
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- 3. ĐƠN ĐẶT PHÒNG MỚI NHẤT --}}
    <div class="col-lg-7">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="m-0 font-weight-bold text-dark"><i class="fa-solid fa-clock me-2"></i> Đơn Đặt Gần Đây</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Khách hàng</th>
                                <th>Phòng</th>
                                <th>Tổng tiền</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentBookings as $booking)
                            <tr>
                                <td class="ps-3">
                                    <span class="fw-bold text-dark d-block">{{ $booking->name }}</span>
                                    <small class="text-muted">{{ $booking->phone }}</small>
                                </td>
                                <td>{{ Str::limit(str_replace('Phòng: ', '', explode('|', $booking->note)[0]), 15) }}
                                </td>
                                <td class="fw-bold text-danger">{{ number_format($booking->total_price) }} ₫</td>
                                <td>
                                    @if($booking->status == 'pending')
                                    <span class="badge bg-warning text-dark" style="font-size: 0.7rem">Chờ duyệt</span>
                                    @elseif($booking->status == 'confirmed')
                                    <span class="badge bg-success" style="font-size: 0.7rem">Đã duyệt</span>
                                    @else
                                    <span class="badge bg-danger" style="font-size: 0.7rem">Hủy/Khác</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white text-center">
                <a href="{{ route('admin.bookings') }}" class="text-decoration-none small fw-bold">Xem tất cả đơn <i
                        class="fa-solid fa-arrow-right ms-1"></i></a>
            </div>
        </div>
    </div>
</div>
@endsection