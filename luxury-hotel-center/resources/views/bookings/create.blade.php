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
            {{-- Cột trái: Form nhập liệu --}}
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm p-4 mb-4">
                    <h4 class="mb-4 text-uppercase fw-bold text-dark font-playfair">1. Thông tin khách hàng</h4>

                    <div class="row g-3">
                        <div class="col-12 mb-3 p-3 bg-warning bg-opacity-10 border border-warning rounded">
                            <label class="form-label fw-bold text-danger"><i class="fa-solid fa-key"></i> Chọn số phòng
                                cụ thể (Demo 4PC) *</label>
                            <select class="form-select border-danger shadow-sm fw-bold" name="room_id" required>
                                <option value="">-- Click để chọn phòng còn trống --</option>
                                @forelse($availableRooms as $rId)
                                <option value="{{ $rId }}">Phòng số {{ $rId }}</option>
                                @empty
                                <option value="" disabled>Đã hết phòng loại này!</option>
                                @endforelse
                            </select>
                            <small class="text-muted">Hệ thống phân tán sẽ khoá chính xác ID phòng này trên 5
                                Server.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Họ tên *</label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name', Auth::user()->name ?? '') }}" required>
                            @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Email *</label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                value="{{ old('email', Auth::user()->email ?? '') }}" required>
                            @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Số điện thoại *</label>
                            <input type="tel" name="phone" class="form-control @error('phone') is-invalid @enderror"
                                value="{{ old('phone') }}" required>
                            @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
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

                    <h4 class="mb-4 mt-5 text-uppercase fw-bold text-dark font-playfair">2. Chi tiết kỳ nghỉ</h4>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Ngày nhận phòng (Check-in) *</label>
                            <input type="date" name="checkin" id="checkin"
                                class="form-control py-3 @error('checkin') is-invalid @enderror"
                                value="{{ old('checkin') }}" min="{{ date('Y-m-d') }}" required>
                            @error('checkin')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Ngày trả phòng (Check-out) *</label>
                            <input type="date" name="checkout" id="checkout"
                                class="form-control py-3 @error('checkout') is-invalid @enderror"
                                value="{{ old('checkout') }}" min="{{ date('Y-m-d', strtotime('+1 day')) }}" required>
                            @error('checkout')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold small">Ghi chú / Yêu cầu đặc biệt</label>
                            <textarea name="note" class="form-control" rows="3"
                                placeholder="Ví dụ: Giường đôi, tầng cao, view đẹp...">{{ old('note') }}</textarea>
                        </div>
                    </div>



                    <button type="submit" id="btn-submit"
                        class="btn btn-luxury w-100 py-3 mt-4 fw-bold shadow hover-scale text-uppercase">
                        Xác nhận đặt phòng
                    </button>
                </div>
            </div>

            {{-- Cột phải: Thông tin đơn hàng --}}
            <div class="col-lg-4">
                <div class="card border-0 shadow sticky-top" style="top: 100px;">
                    <div class="card-header bg-dark text-white text-center py-3">
                        <h5 class="mb-0 font-playfair text-uppercase">Thông tin đơn hàng</h5>
                    </div>
                    <div class="card-body">
                        <img src="{{ $roomImg }}" class="img-fluid rounded mb-3" alt="Room Image">

                        <h5 class="fw-bold text-warning font-playfair">{{ $roomName }}</h5>
                        <p class="small text-muted mb-3"><i class="fa-solid fa-bed"></i> Giường King • View Đẹp</p>

                        <hr>

                        <ul class="list-group list-group-flush mb-3">
                            <li class="list-group-item d-flex justify-content-between px-0">
                                <span>Đơn giá (đã giảm)</span>
                                <span class="fw-bold">{{ number_format($roomPrice, 0, ',', '.') }} ₫</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between px-0">
                                <span>Số đêm</span>
                                <span class="fw-bold"><span id="nightCount">1</span> đêm</span>
                            </li>
                        </ul>

                        <div
                            class="d-flex justify-content-between align-items-center p-3 bg-light border border-warning rounded">
                            <span class="h6 mb-0 fw-bold">TỔNG CỘNG:</span>
                            <div class="text-end">
                                <div id="loading-spinner" class="spinner-border text-danger spinner-border-sm d-none"
                                    role="status">
                                    <span class="visually-hidden">Đang tính...</span>
                                </div>

                                <span class="h4 mb-0 fw-bold text-danger" id="totalPriceDisplay">
                                    {{ number_format($roomPrice, 0, ',', '.') }} ₫
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkinEl = document.getElementById('checkin');
    const checkoutEl = document.getElementById('checkout');
    const nightCountEl = document.getElementById('nightCount');
    const totalPriceEl = document.getElementById('totalPriceDisplay');
    const hiddenPriceInput = document.getElementById('hiddenPrice');
    const hiddenNightCountInput = document.getElementById('hiddenNightCount');
    const loadingSpinner = document.getElementById('loading-spinner');
    const btnSubmit = document.getElementById('btn-submit');

    const rawPrice = hiddenPriceInput ? parseFloat(hiddenPriceInput.value) : 0;

    // Set ngày mặc định
    const today = new Date();
    const tomorrow = new Date(today);
    tomorrow.setDate(today.getDate() + 1);

    const formatDate = (date) => {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    };

    if (!checkinEl.value) checkinEl.value = formatDate(today);
    if (!checkoutEl.value) checkoutEl.value = formatDate(tomorrow);

    function updatePrice() {
        const checkinDate = new Date(checkinEl.value);
        const checkoutDate = new Date(checkoutEl.value);

        if (checkinDate && checkoutDate && checkoutDate > checkinDate) {
            const diffTime = Math.abs(checkoutDate - checkinDate);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

            loadingSpinner.classList.remove('d-none');
            totalPriceEl.classList.add('d-none');

            setTimeout(() => {
                const totalPrice = diffDays * rawPrice;

                nightCountEl.textContent = diffDays;
                totalPriceEl.textContent = new Intl.NumberFormat('vi-VN').format(totalPrice) + ' ₫';

                hiddenNightCountInput.value = diffDays;

                loadingSpinner.classList.add('d-none');
                totalPriceEl.classList.remove('d-none');
            }, 300);
        } else {
            nightCountEl.textContent = '0';
            totalPriceEl.textContent = '0 ₫';
            hiddenNightCountInput.value = 0;
        }
    }

    checkinEl.addEventListener('change', function() {
        const currentCheckin = new Date(this.value);
        if (currentCheckin) {
            const nextDay = new Date(currentCheckin);
            nextDay.setDate(nextDay.getDate() + 1);

            const currentCheckout = new Date(checkoutEl.value);
            if (!checkoutEl.value || currentCheckout <= currentCheckin) {
                checkoutEl.value = formatDate(nextDay);
            }
        }
        updatePrice();
    });

    checkoutEl.addEventListener('change', updatePrice);

    updatePrice();

    document.getElementById('bookingForm').addEventListener('submit', function(e) {
        const nights = parseInt(hiddenNightCountInput.value);

        if (nights <= 0) {
            e.preventDefault();
            alert('Vui lòng chọn ngày check-out sau ngày check-in!');
            return false;
        }

        btnSubmit.disabled = true;
        btnSubmit.innerHTML =
            '<span class="spinner-border spinner-border-sm me-2"></span>Đang xử lý...';
    });
});

function refreshCaptcha() {
    const captchaImg = document.querySelector('.captcha-img img');
    if (captchaImg) {
        const src = captchaImg.src;
        captchaImg.src = src.split('?')[0] + '?' + Math.random();
    }
}
</script>
@endsection