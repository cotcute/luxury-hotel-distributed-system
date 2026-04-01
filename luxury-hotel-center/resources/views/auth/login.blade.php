@extends('layouts.app')

@section('title', 'Đăng Nhập - Luxury Hotel')

@section('content')
<div class="d-flex align-items-center justify-content-center"
    style="min-height: 80vh; background: url('https://images.unsplash.com/photo-1566073771259-6a8506099945?w=1920') no-repeat center center/cover;">

    <div class="card border-0 shadow-lg p-4" style="width: 400px; background: rgba(255, 255, 255, 0.95);">
        <div class="text-center mb-4">
            <h3 class="font-playfair fw-bold text-dark">ĐĂNG NHẬP</h3>
            <p class="text-muted small">Chào mừng bạn quay trở lại</p>
        </div>

        <form action="{{ route('login') }}" method="POST">
            @csrf

            <div class="mb-3">
                <label class="form-label fw-bold small">Email</label>
                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                    value="{{ old('email') }}" required autofocus>
                @error('email') <span class="invalid-feedback">{{ $message }}</span> @enderror
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold small">Mật khẩu</label>
                <input type="password" name="password" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-luxury w-100 py-2 fw-bold mb-3">ĐĂNG NHẬP</button>

            <div class="text-center small">
                <span>Chưa có tài khoản? </span>
                <a href="{{ route('register') }}" class="text-decoration-none fw-bold text-dark">Đăng ký ngay</a>
            </div>
        </form>
    </div>
</div>
@endsection