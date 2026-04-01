<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoomTypeSeeder extends Seeder
{
    public function run()
    {
        // Xóa dữ liệu cũ
        DB::table('room_types')->truncate();

        $rooms = [
            ['name' => 'Deluxe Ocean View', 'total_rooms' => 10, 'price' => 1500000],
            ['name' => 'Royal Executive', 'total_rooms' => 9, 'price' => 2500000],
            ['name' => 'Signature Penthouse', 'total_rooms' => 14, 'price' => 5000000],
            ['name' => 'Presidential Villa', 'total_rooms' => 2, 'price' => 10000000], // Chỉ có 2 căn
        ];

        DB::table('room_types')->insert($rooms);
    }
}