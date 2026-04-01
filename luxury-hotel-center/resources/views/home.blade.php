@extends('layouts.app')

@section('title', 'Trang Chủ - Luxury Hotel')

@section('content')

<div class="position-relative vh-100 d-flex align-items-center justify-content-center overflow-hidden">
    <img src="https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80"
        class="position-absolute w-100 h-100 object-fit-cover" style="filter: brightness(0.4); z-index: -1;"
        alt="Hero BG">

    <div class="text-center text-white p-4" data-aos="zoom-in" data-aos-duration="1500">
        <h5 class="text-uppercase letter-spacing-2 mb-3 text-warning">Chào mừng đến với</h5>
        <h1 class="display-1 fw-bold font-playfair mb-4">LUXURY HOTEL</h1>
        <p class="lead mb-5 d-none d-md-block">Nơi cảm xúc thăng hoa và đẳng cấp được khẳng định</p>

        <a href="{{ route('booking.create') }}" class="btn btn-luxury btn-lg py-3 px-5 fw-bold shadow-lg">
            ĐẶT PHÒNG NGAY <i class="fa-solid fa-arrow-right ms-2"></i>
        </a>
    </div>
</div>

<section class="py-5">
    <div class="container py-5">
        <div class="row align-items-center">
            <div class="col-lg-6" data-aos="fade-right">
                <img src="https://images.unsplash.com/photo-1566073771259-6a8506099945?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80"
                    class="img-fluid rounded shadow-lg" alt="About Hotel">
            </div>
            <div class="col-lg-6 ps-lg-5" data-aos="fade-left">
                <h5 class="text-warning text-uppercase letter-spacing-2">Về chúng tôi</h5>
                <h2 class="font-playfair display-5 mb-4">Tận Hưởng Kỳ Nghỉ Trong Mơ</h2>
                <p class="text-muted mb-4">Kiến trúc hiện đại pha lẫn nét cổ điển, mang lại trải nghiệm không thể nào
                    quên. Chúng tôi cam kết mang đến dịch vụ 5 sao chuẩn quốc tế ngay tại Việt Nam.</p>

                <div class="row g-4 mb-4">
                    <div class="col-6">
                        <div class="d-flex align-items-center">
                            <i class="fa-solid fa-water fa-2x text-warning me-3"></i>
                            <div>
                                <h6 class="mb-0 fw-bold">View Biển</h6>
                                <small class="text-muted">100% Phòng</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="d-flex align-items-center">
                            <i class="fa-solid fa-bell-concierge fa-2x text-warning me-3"></i>
                            <div>
                                <h6 class="mb-0 fw-bold">Phục vụ 24/7</h6>
                                <small class="text-muted">Chuyên nghiệp</small>
                            </div>
                        </div>
                    </div>
                </div>

                <a href="#rooms" class="btn btn-outline-dark rounded-0 px-4 py-2">XEM THÊM</a>
            </div>
        </div>
    </div>
</section>

<section id="rooms" class="py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <h5 class="text-warning text-uppercase letter-spacing-2">Phòng & Suites</h5>
            <h2 class="font-playfair">Ưu Đãi Đặc Biệt Hôm Nay</h2>
        </div>

        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">

            {{-- Vòng lặp hiển thị phòng từ Controller --}}
            @foreach($rooms as $room)

            {{-- BỔ SUNG: Tính toán số phòng trống Real-time trực tiếp từ Database --}}
            @php
            $roomInstances = [
            'Deluxe Ocean View' => [101, 102, 103, 104, 105, 106, 107, 108, 109, 110],
            'Royal Executive' => [201, 202, 203, 204, 205, 206],
            'Signature Penthouse' => [301, 302, 303, 304],
            'Presidential Villa' => [401, 402]
            ];
            $ids = $roomInstances[$room['name']] ?? [];
            $totalRooms = count($ids);

            // Đếm số phòng đã bị Khóa (pending) hoặc Đã bán (confirmed, checked_in)
            $bookedRooms = \App\Models\Booking::whereIn('room_id', $ids)
            ->whereIn('status', ['pending', 'confirmed', 'checked_in'])
            ->count();

            $availableRooms = max(0, $totalRooms - $bookedRooms);
            @endphp

            <div class="col" data-aos="fade-up" data-aos-delay="100">
                <div
                    class="card border-0 shadow-sm h-100 room-card {{ $room['discount'] == 'VIP' ? 'border-warning' : '' }}">
                    <div class="overflow-hidden position-relative">
                        <img src="{{ $room['img'] }}" class="card-img-top room-img transition-zoom">

                        {{-- Badge giảm giá --}}
                        @if($room['discount'] == 'VIP')
                        <span
                            class="position-absolute top-50 start-50 translate-middle badge bg-warning text-dark px-3 py-2">VIP
                            Only</span>
                        @else
                        <span
                            class="position-absolute top-0 end-0 bg-danger text-white px-3 py-1 m-2 small fw-bold">{{ $room['discount'] }}</span>
                        @endif

                        {{-- HIỂN THỊ SỐ PHÒNG TRỐNG REAL-TIME --}}
                        <div
                            class="position-absolute bottom-0 start-0 w-100 p-2 bg-dark bg-opacity-75 text-white text-center">
                            @if($availableRooms > 0)
                            <small class="fw-bold text-warning"><i class="fa-solid fa-check-circle"></i> Còn
                                {{ $availableRooms }} phòng trống</small>
                            @else
                            <small class="fw-bold text-danger"><i class="fa-solid fa-times-circle"></i> Hết
                                phòng</small>
                            @endif
                        </div>
                    </div>

                    <div class="card-body text-center p-4 {{ $room['discount'] == 'VIP' ? 'bg-dark text-white' : '' }}">
                        <h5 class="font-playfair {{ $room['discount'] == 'VIP' ? 'text-warning' : '' }}">
                            {{ $room['name'] }}</h5>

                        <div class="text-warning mb-2 small">
                            <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i
                                class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i
                                class="fa-solid fa-star"></i>
                        </div>

                        @if($room['old_price'])
                        <div class="text-muted text-decoration-line-through small">
                            {{ number_format($room['old_price']) }} ₫</div>
                        @endif

                        <h5 class="{{ $room['discount'] == 'VIP' ? 'text-white' : 'text-danger' }} fw-bold">
                            {{ number_format($room['price']) }} ₫
                        </h5>

                        {{-- Nút đặt phòng: Nếu hết phòng thì Disable --}}
                        @if($availableRooms > 0)
                        <a href="{{ route('booking.create', ['room_name' => $room['name'], 'price' => $room['price'], 'img' => $room['img']]) }}"
                            class="btn {{ $room['discount'] == 'VIP' ? 'btn-light' : 'btn-dark' }} w-100 rounded-0 mt-2">
                            ĐẶT NGAY
                        </a>
                        @else
                        <button class="btn btn-secondary w-100 rounded-0 mt-2" disabled>TẠM HẾT PHÒNG</button>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach

        </div>
    </div>
</section>

<section id="services" class="py-5">
    <div class="container py-5">
        <div class="text-center mb-5" data-aos="fade-up">
            <h5 class="text-warning text-uppercase letter-spacing-2">Tiện Ích & Dịch Vụ</h5>
            <h2 class="font-playfair">Trải Nghiệm Đẳng Cấp 5 Sao</h2>
        </div>
        <div class="row g-4 text-center">
            <div class="col-md-4" data-aos="zoom-in" data-aos-delay="100">
                <i class="fa-solid fa-utensils fa-3x text-warning mb-3"></i>
                <h4 class="font-playfair">Nhà Hàng Á - Âu</h4>
                <p class="text-muted">Thưởng thức ẩm thực tinh hoa từ các đầu bếp hàng đầu Michelin.</p>
            </div>
            <div class="col-md-4" data-aos="zoom-in" data-aos-delay="200">
                <i class="fa-solid fa-spa fa-3x text-warning mb-3"></i>
                <h4 class="font-playfair">Luxury Spa</h4>
                <p class="text-muted">Liệu trình thư giãn, tái tạo năng lượng hoàn hảo.</p>
            </div>
            <div class="col-md-4" data-aos="zoom-in" data-aos-delay="300">
                <i class="fa-solid fa-person-swimming fa-3x text-warning mb-3"></i>
                <h4 class="font-playfair">Hồ Bơi Gần Biển</h4>
                <p class="text-muted">Ngắm nhìn toàn cảnh biển Đà Nẵng từ hồ bơi tầng thượng.</p>
            </div>
        </div>
    </div>
</section>

<section id="contact" class="py-5 bg-dark text-white position-relative">
    <img src="https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80"
        class="position-absolute w-100 h-100 object-fit-cover top-0 start-0"
        style="filter: brightness(0.2); opacity: 0.5; z-index: 0;" alt="Contact BG">

    <div class="container position-relative" style="z-index: 1;">
        <div class="row">
            <div class="col-lg-5 mb-5 mb-lg-0" data-aos="fade-right">
                <h5 class="text-warning text-uppercase letter-spacing-2">Liên Hệ Với Chúng Tôi</h5>
                <h2 class="font-playfair display-5 mb-4">Hỗ Trợ Khách Hàng 24/7</h2>
                <p class="mb-4 text-light opacity-75">Chúng tôi luôn sẵn sàng lắng nghe mọi ý kiến đóng góp của quý
                    khách.</p>

                <div class="d-flex align-items-center mb-3">
                    <div class="bg-warning text-dark rounded-circle d-flex align-items-center justify-content-center me-3"
                        style="width: 40px; height: 40px;">
                        <i class="fa-solid fa-location-dot"></i>
                    </div>
                    <span>19 Trường Sa, Ngũ Hành Sơn, Đà Nẵng, Việt Nam</span>
                </div>
                <div class="d-flex align-items-center mb-3">
                    <div class="bg-warning text-dark rounded-circle d-flex align-items-center justify-content-center me-3"
                        style="width: 40px; height: 40px;">
                        <i class="fa-solid fa-phone"></i>
                    </div>
                    <span>0325799103</span>
                </div>
                <div class="d-flex align-items-center">
                    <div class="bg-warning text-dark rounded-circle d-flex align-items-center justify-content-center me-3"
                        style="width: 40px; height: 40px;">
                        <i class="fa-solid fa-envelope"></i>
                    </div>
                    <span>CEOK@luxuryhotel.com</span>
                </div>
            </div>

            <div class="col-lg-6 offset-lg-1" data-aos="fade-left">
                <div class="bg-white p-4 rounded shadow-lg">
                    <h3 class="font-playfair text-dark mb-4 text-center">Gửi Tin Nhắn</h3>

                    @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fa-solid fa-check-circle me-2"></i>{{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    @endif

                    <form action="{{ route('contact.send') }}" method="POST" id="contactForm">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-floating text-dark">
                                    <input type="text" class="form-control" id="name" name="name" placeholder="Họ tên"
                                        required>
                                    <label for="name">Họ tên *</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating text-dark">
                                    <input type="email" class="form-control" id="email" name="email" placeholder="Email"
                                        required>
                                    <label for="email">Email *</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-floating text-dark">
                                    <textarea class="form-control" placeholder="Tin nhắn" id="message" name="message"
                                        style="height: 120px" required></textarea>
                                    <label for="message">Nội dung *</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <button class="btn btn-luxury w-100 py-3 fw-bold" type="submit" id="btnSubmit">
                                    GỬI YÊU CẦU <i class="fa-solid fa-paper-plane ms-2"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.room-card:hover .room-img {
    transform: scale(1.1);
}

.room-img {
    transition: transform 0.5s ease;
    height: 250px;
    object-fit: cover;
}

html {
    scroll-behavior: smooth;
}
</style>

@endsection

@section('scripts')
<script>
document.getElementById('contactForm').addEventListener('submit', function() {
    const btn = document.getElementById('btnSubmit');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Đang gửi...';
});
</script>
@endsection