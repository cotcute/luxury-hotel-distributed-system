<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * =====================================================
 * MIGRATION 1: BẢNG ROOMS (Quản lý thông tin phòng)
 * =====================================================
 */
return new class extends Migration
{
    public function up()
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Tên phòng: "Deluxe Ocean View"
            $table->string('room_number')->unique(); // Số phòng: "101", "102"
            $table->string('type'); // Loại: "deluxe", "suite", "villa"
            $table->decimal('price', 12, 2); // Giá gốc
            $table->integer('max_guests')->default(2); // Số khách tối đa
            $table->decimal('area', 8, 2)->nullable(); // Diện tích (m²)
            $table->text('description')->nullable();
            $table->string('image_url')->nullable();
            
            // QUAN TRỌNG: Trạng thái phòng
            $table->enum('status', [
                'available',      // Có sẵn
                'occupied',       // Đang sử dụng
                'cleaning',       // Đang dọn dẹp
                'maintenance',    // Bảo trì
                'blocked'         // Bị khóa
            ])->default('available');
            
            // Thông tin thêm
            $table->json('amenities')->nullable(); // ["wifi", "tv", "minibar"]
            $table->boolean('is_active')->default(true); // Còn kinh doanh không?
            
            $table->timestamps();
            
            // Indexes để query nhanh
            $table->index('type');
            $table->index('status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('rooms');
    }
};