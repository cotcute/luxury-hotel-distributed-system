<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckAdmin
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // 1. Kiểm tra đã đăng nhập chưa?
        if (Auth::check()) {
            
            // 2. Kiểm tra cột 'role' trong database có phải là 'admin' không?
            // Lưu ý: Cột trong DB của bạn tên là 'role', giá trị là 'admin' (như ảnh bạn gửi)
            if (Auth::user()->role == 'admin') {
                return $next($request); // Cho phép đi tiếp vào trang Admin
            }
        }

        // 3. Nếu không phải admin, đá về trang chủ và báo lỗi
        return redirect('/')->with('error', 'Bạn không có quyền truy cập trang Quản trị!');
    }
}