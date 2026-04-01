<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use HasFactory;

    /**
     * Bảng trong database
     */
    protected $table = 'contacts';

    /**
     * QUAN TRỌNG: Các trường được phép Mass Assignment
     * Chỉ khai báo những field mà người dùng có thể gửi lên
     */
    protected $fillable = [
        'name',
        'email', 
        'message',
    ];

    /**
     * Các trường KHÔNG được phép Mass Assignment (tùy chọn)
     * Nếu dùng $fillable thì không cần $guarded
     */
    // protected $guarded = ['id'];

    /**
     * Tự động quản lý timestamps (created_at, updated_at)
     */
    public $timestamps = true;

    /**
     * Cast kiểu dữ liệu (tùy chọn)
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}