<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $fillable = [
        'room_number',
        'room_type',
        'price',
        'capacity',
        'status'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'capacity' => 'integer'
    ];

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }
}
