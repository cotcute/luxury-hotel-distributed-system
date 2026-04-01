<nav class="navbar navbar-expand-lg navbar-dark navbar-luxury fixed-top">
    <div class="container">
        <!-- 1. LOGO -->
        <a class="navbar-brand fs-3 text-white" href="{{ route('home') }}">
            <i class="fa-solid fa-hotel" style="color: #c9a050;"></i> LUXURY <span style="color: #c9a050;">HOTEL</span>
        </a>

        <!-- 2. TOGGLER (MOBILE) -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">

            <!-- 3. MENU CHÍNH (Sử dụng anchor link /#id) -->
            <ul class="navbar-nav mx-auto">
                <li class="nav-item">
                    <a class="nav-link {{ Request::is('/') ? 'active' : '' }}" href="{{ url('/') }}">Trang chủ</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ url('/#rooms') }}">Phòng & Suites</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ url('/#services') }}">Dịch vụ</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ url('/#contact') }}">Liên hệ</a>
                </li>
            </ul>

            <!-- 4. KHU VỰC TÀI KHOẢN & CTA -->
            <ul class="navbar-nav">
                @guest
                <!-- A. CHƯA ĐĂNG NHẬP -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle btn btn-outline-light px-3 rounded-0" href="#" role="button"
                        data-bs-toggle="dropdown">
                        <i class="fa-regular fa-user me-2"></i> Tài khoản
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-luxury">
                        <li><a class="dropdown-item" href="{{ route('login') }}">Đăng nhập</a></li>
                        <li>
                            <hr class="dropdown-divider bg-secondary">
                        </li>
                        <li><a class="dropdown-item" href="{{ route('register') }}">Đăng ký thành viên</a></li>
                    </ul>
                </li>
                @else
                <!-- B. ĐÃ ĐĂNG NHẬP -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button"
                        data-bs-toggle="dropdown">
                        <!-- Avatar tự động theo tên -->
                        <img src="https://ui-avatars.com/api/?name={{ Auth::user()->name }}&background=c9a050&color=fff"
                            alt="Avatar" class="rounded-circle me-2" width="35" height="35">
                        <span>Xin chào, {{ Auth::user()->name }}</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-luxury">
                        <!-- QUAN TRỌNG: Link trỏ về Dashboard -->
                        <li>
                            <a class="dropdown-item" href="{{ route('dashboard') }}">
                                <i class="fa-solid fa-list-check me-2"></i> Lịch sử đặt phòng
                            </a>
                        </li>

                        <li><a class="dropdown-item" href="#"><i class="fa-solid fa-gear me-2"></i> Cài đặt</a></li>

                        <li>
                            <hr class="dropdown-divider bg-secondary">
                        </li>

                        <!-- Nút Đăng Xuất (Form POST an toàn) -->
                        <li>
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger">
                                    <i class="fa-solid fa-right-from-bracket me-2"></i> Đăng xuất
                                </button>
                            </form>
                        </li>
                    </ul>
                </li>
                @endguest

                <!-- 5. NÚT ĐẶT PHÒNG (LUÔN HIỂN THỊ) -->
                <li class="nav-item ms-3">
                    <a href="{{ route('booking.create') }}" class="btn btn-luxury">ĐẶT PHÒNG NGAY</a>
                </li>
            </ul>
        </div>
    </div>
</nav>