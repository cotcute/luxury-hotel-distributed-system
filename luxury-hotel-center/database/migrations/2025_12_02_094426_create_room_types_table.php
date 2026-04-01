<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
{
    Schema::create('room_types', function (Blueprint $table) {
        $table->id();
        $table->string('name'); // Tên loại phòng (Khớp với tên ở trang chủ)
        $table->integer('total_rooms')->default(1); // Tổng số lượng phòng
        $table->decimal('price', 15, 2);
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('room_types');
    }
};