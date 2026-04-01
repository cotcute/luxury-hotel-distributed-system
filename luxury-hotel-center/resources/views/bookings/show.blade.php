@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-lg">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0">✓ Đặt Phòng Thành Công!</h4>
                </div>

                <div class="card-body">
                    <div class="alert alert-info">
                        <p class="mb-0">Đơn đặt phòng của bạn đã được ghi nhận. Admin sẽ xác nhận trong thời gian sớm nhất.</p>
                    </div>

                    <h5 class="mt-4 mb-3">Chi Tiết Đặt Phòng</h5>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p><strong>Mã Booking:</strong> #{{ $booking->id }}</p>
                            <p><strong>Trạng Thái:</strong> 
                                <span class="badge bg-warning text-dark">{{ ucfirst($booking->status) }}</span>
                            </p>
                            <p><strong>Họ Tên:</strong> {{ $booking->full_name }}</p>
                            <p><strong>Email:</strong> {{ $booking->email }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Số Điện Thoại:</strong> {{ $booking->phone }}</p>
                            <p><strong>Quốc Tịch:</strong> {{ $booking->nationality }}</p>
                            <p><strong>Ngày Đặt:</strong> {{ $booking->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                    </div>

                    <hr>

                    <h5 class="mb-3">Chi Tiết Kỳ Nghỉ</h5>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p><strong>Ngày Nhận Phòng:</strong> {{ $booking->check_in->format('d/m/Y') }}</p>
                            <p><strong>Ngày Trả Phòng:</strong> {{ $booking->check_out->format('d/m/Y') }}</p>
                            <p><strong>Số Đêm:</strong> 
                                {{ $booking->check_out->diffInDays($booking->check_in) }} đêm
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Giá/Đêm:</strong> {{ number_format($booking->total_price) }}đ</p>
                            <p><strong>Tổng Tiền:</strong> 
                                <span class="text-danger fs-5">{{ number_format($booking->total_price) }}đ</span>
                            </p>
                        </div>
                    </div>

                    @if($booking->notes)
                        <hr>
                        <h5 class="mb-3">Ghi Chú</h5>
                        <p>{{ $booking->notes }}</p>
                    @endif

                    <hr>

                    <div class="alert alert-success">
                        <h6 class="mb-2">📧 Chúng tôi đã gửi xác nhận đến:</h6>
                        <p class="mb-0"><strong>{{ $booking->email }}</strong></p>
                        <small class="text-muted">Vui lòng kiểm tra email để nhận thông tin chi tiết</small>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <a href="{{ route('home') }}" class="btn btn-primary btn-lg">← Quay Lại Trang Chủ</a>
                        <a href="{{ route('dashboard') }}" class="btn btn-info btn-lg">Xem Dashboard</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
