<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('room_prices', function (Blueprint $table) {
            $table->id();

            // Liên kết tới phòng
            $table->foreignId('room_id')
                ->constrained()
                ->onDelete('cascade');

            // Khoảng thời gian áp dụng giá
            $table->date('start_date');
            $table->date('end_date');

            // Giá đặc biệt theo mùa / dịp lễ
            $table->decimal('price', 12, 2);

            // Tên mùa - ví dụ: "Tết", "Hè", "Lễ 30/4"
            $table->string('season_name')->nullable();

            $table->timestamps();

            // Tối ưu truy vấn + ngăn trùng ngày
            $table->index(['room_id', 'start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_prices');
    }};