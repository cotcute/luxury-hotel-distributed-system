@extends('layouts.app')

@section('title', 'Đặt Phòng - Luxury Hotel')

@section('content')
<div class="position-relative" style="height: 300px; overflow: hidden;">
    <img src="https://images.unsplash.com/photo-1578683010236-d716f9a3f461?w=1920" class="w-100 h-100 object-fit-cover"
        style="filter: brightness(0.6);">
    <div class="position-absolute top-50 start-50 translate-middle text-center text-white w-100">
        <h1 class="display-4 fw-bold font-playfair">Hoàn Tất Đặt Phòng</h1>
    </div>
</div>

<div class="container my-5">
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fa-solid fa-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fa-solid fa-exclamation-circle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if($errors->any())
    <div class="alert alert-warning alert-dismissible fade show">
        <strong>Vui lòng kiểm tra lại:</strong>
        <ul class="mb-0 mt-2">
            @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <form action="{{ route('booking.store') }}" method="POST" id="bookingForm">
        @csrf

        <input type="hidden" name="room_name" value="{{ $roomName }}">
        <input type="hidden" name="real_price" id="hiddenPrice" value="{{ $roomPrice }}">
        <input type="hidden" name="night_count" id="hiddenNightCount" value="1">

        <div class="row">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm p-4 mb-4">
                    <h4 class="mb-4 text-uppercase fw-bold text-dark font-playfair">1. Thông tin khách hàng</h4>

                    <div class="row g-3">

                        {{-- CHỌN PHÒNG --}}
                        <div class="col-12 mb-3 p-3 bg-warning bg-opacity-10 border border-warning rounded">
                            <label class="form-label fw-bold text-danger">
                                <i class="fa-solid fa-key"></i> Chọn số phòng (Demo 4PC) *
                            </label>

                            <select class="form-select border-danger shadow-sm fw-bold" name="room_id" required>
                                <option value="">-- Click để chọn phòng còn trống --</option>
                                @forelse($availableRooms as $rId)
                                <option value="{{ $rId }}">Phòng số {{ $rId }}</option>
                                @empty
                                <option disabled>Đã hết phòng loại này!</option>
                                @endforelse
                            </select>

                            <small class="text-muted">
                                Hệ thống sẽ khóa chính xác ID phòng này trên 5 Server.
                            </small>
                        </div>

                        {{-- 🔥 CHỌN SERVER (MỚI THÊM) --}}
                        <div class="col-12 mb-3 p-3 bg-danger bg-opacity-10 border border-danger rounded">
                            <label for="target_node" class="form-label fw-bold text-danger">
                                <i class="fas fa-server"></i> Chọn Server Xử Lý (Demo Phân Tán) *
                            </label>

                            <select name="target_node" id="target_node"
                                class="form-select border-danger shadow-sm fw-bold" required>
                                <option value="">-- Click để chọn Server nhận lệnh --</option>
                                <option value="https://node-1-khanh.onrender.com">Server Node 1 (Khánh)</option>
                                <option value="https://node-2-khai-80yz.onrender.com">Server Node 2 (Khải)</option>
                                <option value="https://node-3-ngocc.onrender.com">Server Node 3 (Ngọc)</option>
                                <option value="https://node-kien.onrender.com">Server Node 4 (Kiên)</option>
                                <option value="https://node-5-duy-b0ca.onrender.com">Server Node 5 (Duy)</option>
                            </select>

                            <small class="text-muted">
                                Request sẽ gửi trực tiếp đến server này. Server sẽ đóng vai trò "nhạc trưởng" điều phối
                                hệ thống.
                            </small>
                        </div>

                        {{-- FORM THÔNG TIN --}}
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Họ tên *</label>
                            <input type="text" name="name" class="form-control"
                                value="{{ old('name', Auth::user()->name ?? '') }}" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Email *</label>
                            <input type="email" name="email" class="form-control"
                                value="{{ old('email', Auth::user()->email ?? '') }}" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold small">SĐT *</label>
                            <input type="tel" name="phone" class="form-control" value="{{ old('phone') }}" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Quốc tịch</label>
                            <select class="form-select" name="country">
                                <option value="VN">Việt Nam</option>
                                <option value="US">Trung Quốc</option>
                                <option value="JP">Nước Ngoài</option>
                                <option value="KR">Korea</option>
                            </select>
                        </div>
                    </div>

                    <h4 class="mt-5 mb-3 fw-bold">2. Chi tiết kỳ nghỉ</h4>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <input type="date" name="checkin" id="checkin" class="form-control" required>
                        </div>

                        <div class="col-md-6">
                            <input type="date" name="checkout" id="checkout" class="form-control" required>
                        </div>

                        <div class="col-12">
                            <textarea name="note" class="form-control" rows="3"></textarea>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-danger w-100 mt-4 fw-bold">
                        Xác nhận đặt phòng
                    </button>
                </div>
            </div>

            {{-- CỘT PHẢI --}}
            <div class="col-lg-4">
                <div class="card shadow">
                    <div class="card-body text-center">
                        <img src="{{ $roomImg }}" class="img-fluid mb-3">

                        <h5>{{ $roomName }}</h5>
                        <p>{{ number_format($roomPrice) }} ₫ / đêm</p>

                        <h4 id="totalPriceDisplay">{{ number_format($roomPrice) }} ₫</h4>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection