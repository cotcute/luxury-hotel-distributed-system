@extends('layouts.app')

@section('title', 'Đăng Ký - Luxury Hotel')

@section('content')
<div class="d-flex align-items-center justify-content-center"
    style="min-height: 80vh; background: url('https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?w=1920') no-repeat center center/cover;">

    <div class="card border-0 shadow-lg p-4" style="width: 450px; background: rgba(255, 255, 255, 0.95);">
        <div class="text-center mb-4">
            <h3 class="font-playfair fw-bold text-dark">ĐĂNG KÝ THÀNH VIÊN</h3>
            <p class="text-muted small">Trải nghiệm những đặc quyền riêng biệt</p>
        </div>

        <form action="{{ route('register') }}" method="POST">
            @csrf

            <div class="mb-3">
                <label class="form-label fw-bold small">Họ và tên</label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                    value="{{ old('name') }}" required>
                @error('name') <span class="invalid-feedback">{{ $message }}</span> @enderror
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold small">Email</label>
                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                    value="{{ old('email') }}" required>
                @error('email') <span class="invalid-feedback">{{ $message }}</span> @enderror
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold small">Mật khẩu</label>
                <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                    required>
                @error('password') <span class="invalid-feedback">{{ $message }}</span> @enderror
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold small">Xác nhận mật khẩu</label>
                <input type="password" name="password_confirmation" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-luxury w-100 py-2 fw-bold mb-3">ĐĂNG KÝ</button>

            <div class="text-center small">
                <span>Đã có tài khoản? </span>
                <a href="{{ route('login') }}" class="text-decoration-none fw-bold text-dark">Đăng nhập</a>
            </div>
        </form>
    </div>
</div>
@endsection