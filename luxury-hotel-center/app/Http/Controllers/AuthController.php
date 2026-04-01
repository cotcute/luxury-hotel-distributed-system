<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // 1. Hiển thị Form Đăng nhập
    public function showLogin() {
        return view('auth.login');
    }

    // 2. Hiển thị Form Đăng ký
    public function showRegister() {
        return view('auth.register');
    }

    // 3. Xử lý Đăng ký
    public function register(Request $request) {
        // Validate dữ liệu
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed', // Cần input password_confirmation
        ], [
            'email.unique' => 'Email này đã được đăng ký.',
            'password.confirmed' => 'Mật khẩu xác nhận không khớp.',
            'password.min' => 'Mật khẩu phải có ít nhất 6 ký tự.'
        ]);

        // Tạo User mới
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Đăng nhập luôn sau khi đăng ký
        Auth::login($user);

        return redirect('/')->with('success', 'Chào mừng thành viên mới! Hãy trải nghiệm kỳ nghỉ dưỡng.');
    }

    // 4. Xử lý Đăng nhập (ĐÃ SỬA LOGIC ADMIN)
    public function login(Request $request) {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            
            // --- LOGIC PHÂN QUYỀN MỚI ---
            // Nếu là Admin -> Vào Dashboard Admin
            // (Lưu ý: Bạn phải đảm bảo route 'admin.dashboard' đã tồn tại trong web.php)
            if (Auth::user()->role == 'admin') {
                return redirect()->route('admin.dashboard')->with('success', 'Chào mừng Admin quay trở lại!');
            }

            // Nếu là khách -> Về trang chủ
            return redirect('/')->with('success', 'Đăng nhập thành công.');
        }

        return back()->withErrors([
            'email' => 'Email hoặc mật khẩu không chính xác.',
        ])->onlyInput('email');
    }

    // 5. Xử lý Đăng xuất
    public function logout(Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/')->with('success', 'Bạn đã đăng xuất thành công.');
    }
}