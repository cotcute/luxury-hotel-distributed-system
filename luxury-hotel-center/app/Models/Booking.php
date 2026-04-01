<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = [
        // Cũ
        'user_id',
        'full_name',
        'email',
        'phone',
        'nationality',
        'check_in',
        'check_out',
        'total_price',
        'notes',
        'status',

        // Mới thêm
        'room_id',
        'name',
        'checkin',
        'checkout',
        'total_nights',
        'note',
        'admin_note',
        'confirmed_by',
        'confirmed_at'
    ];

    protected $casts = [
        'check_in'      => 'date',
        'check_out'     => 'date',
        'checkin'       => 'date',
        'checkout'      => 'date',
        'note',
        'confirmed_at'  => 'datetime',
        'total_price'   => 'decimal:2'
    ];

    // Quan hệ user tạo booking
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Quan hệ phòng
    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    // Admin xác nhận booking
    public function confirmedBy()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }
}