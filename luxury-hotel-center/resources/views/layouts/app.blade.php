<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Luxury Hotel')</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Lato:wght@400;700&display=swap"
        rel="stylesheet">

    <style>
    body {
        font-family: 'Lato', sans-serif;
        background-color: #f8f9fa;
    }

    .font-playfair {
        font-family: 'Playfair Display', serif;
    }

    .navbar-luxury {
        background-color: rgba(20, 20, 20, 0.95);
        backdrop-filter: blur(10px);
        padding: 15px 0;
    }

    .btn-luxury {
        background-color: #c9a050;
        color: #fff;
        border: none;
        transition: 0.3s;
    }

    .btn-luxury:hover {
        background-color: #b08d45;
        color: #fff;
    }

    .text-warning {
        color: #c9a050 !important;
    }

    /* Fix lỗi hiển thị date input */
    input[type="date"] {
        position: relative;
    }
    </style>
</head>

<body>

    @include('partials.header')

    <main>
        @yield('content')
    </main>

    <footer class="bg-dark text-white py-4 mt-5 text-center">
        <small>&copy; Luxury Hotel. Designed by Khanh23CNTT3.</small>
    </footer>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

    <script>
    AOS.init(); // Khởi tạo hiệu ứng chuyển động
    </script>

    @yield('scripts')

</body>

</html>