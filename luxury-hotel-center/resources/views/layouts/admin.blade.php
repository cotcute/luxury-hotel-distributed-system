<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Admin Panel - Luxury Hotel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    body {
        background-color: #f4f6f9;
    }

    .sidebar {
        min-height: 100vh;
        background: #343a40;
        color: #fff;
    }

    .sidebar a {
        color: #cfd8dc;
        text-decoration: none;
        display: block;
        padding: 10px 20px;
    }

    .sidebar a:hover,
    .sidebar a.active {
        background: #495057;
        color: #fff;
        border-left: 4px solid #c9a050;
    }

    .card-stat {
        border: none;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }
    </style>
</head>

<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <div class="sidebar d-flex flex-column flex-shrink-0 p-3" style="width: 250px;">
            <a href="{{ route('admin.dashboard') }}" class="fs-4 fw-bold mb-4 text-center text-warning">
                <i class="fa-solid fa-hotel"></i> ADMIN
            </a>
            <ul class="nav nav-pills flex-column mb-auto">
                <li class="nav-item">
                    <a href="{{ route('admin.dashboard') }}" class="{{ Request::is('admin') ? 'active' : '' }}">
                        <i class="fa-solid fa-gauge me-2"></i> Tổng quan
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.bookings') }}"
                        class="{{ Request::is('admin/bookings*') ? 'active' : '' }}">
                        <i class="fa-solid fa-calendar-check me-2"></i> Quản lý Đặt phòng
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.messages') }}"
                        class="{{ Request::is('admin/messages*') ? 'active' : '' }}">
                        <i class="fa-solid fa-envelope me-2"></i> Liên hệ khách hàng
                    </a>
                </li>
                <li class="mt-4 border-top pt-3">
                    <a href="{{ route('home') }}" target="_blank"><i class="fa-solid fa-globe me-2"></i> Xem trang
                        web</a>
                </li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="flex-grow-1 p-4">
            @yield('content')
        </div>
    </div>
</body>

</html>