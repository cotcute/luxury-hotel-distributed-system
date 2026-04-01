<?php
use App\Http\Controllers\BookingController;

use Illuminate\Support\Facades\Route;

// Import đầy đủ Controller
use App\Http\Controllers\HomeController;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AdminController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// ---------------------------------------------------------
// 1. CÔNG KHAI (PUBLIC)
// ---------------------------------------------------------
Route::get('/', [HomeController::class, 'index'])->name('home');

// Xử lý form liên hệ
Route::post('/contact/send', [HomeController::class, 'sendContact'])->name('contact.send');


// ---------------------------------------------------------
// BOOKING ROUTES
// ---------------------------------------------------------
Route::prefix('booking')->group(function () {

    // Form tạo booking
    Route::get('/create', [BookingController::class, 'create'])
        ->name('booking.create');

    // Route nhận dữ liệu lưu booking (SỬA CHUẨN THEO YÊU CẦU)
    Route::post('/store', [BookingController::class, 'store'])
        ->name('booking.store');

    // Refresh Captcha
    Route::get('/refresh-captcha', [BookingController::class, 'refreshCaptcha'])
        ->name('booking.refreshCaptcha');
});


// ---------------------------------------------------------
// 2. KHÁCH VÃNG LAI (GUEST)
// ---------------------------------------------------------
Route::middleware('guest')->group(function () {

    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');

    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.post');
});


// ---------------------------------------------------------
// 3. THÀNH VIÊN & ADMIN (AUTH)
// ---------------------------------------------------------
Route::middleware('auth')->group(function () {

    // Đăng xuất
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Dashboard chung cho user
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');


    // -----------------------------------------------------
    // ADMIN AREA
    // -----------------------------------------------------
    Route::middleware('admin')->prefix('admin')->group(function () {

        Route::get('/', [AdminController::class, 'index'])
            ->name('admin.dashboard');

        // QUẢN LÝ ĐẶT PHÒNG
        Route::get('/bookings', [AdminController::class, 'bookings'])
            ->name('admin.bookings');

        Route::get('/bookings/{id}', [AdminController::class, 'showBooking'])
            ->name('admin.bookings.show');

        Route::post('/bookings/{id}/update', [AdminController::class, 'updateBookingStatus'])
            ->name('admin.bookings.update');

        // QUẢN LÝ TIN NHẮN
        Route::get('/messages', [AdminController::class, 'messages'])
            ->name('admin.messages');
    });
});