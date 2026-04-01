<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tạo bảng bookings chuẩn xác
     */
    public function up()
    {
        // Kiểm tra nếu bảng đã tồn tại thì xóa đi tạo lại cho sạch
        Schema::dropIfExists('bookings');

        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            
            // 1. Khóa ngoại liên kết User (Cho phép null nếu khách vãng lai)
            $table->unsignedBigInteger('user_id')->nullable();
            
            // 2. Khóa ngoại liên kết Phòng (Quan trọng để thống kê)
            $table->unsignedBigInteger('room_id')->nullable();
            
            // 3. Thông tin khách hàng
            $table->string('name');         // Tên khách
            $table->string('email');        // Email
            $table->string('phone');        // SĐT
            
            // FIX LỖI 1364: Cho phép null để không bao giờ bị lỗi thiếu giá trị mặc định
            $table->string('nationality')->nullable()->default('Vietnam'); 

            // 4. Thông tin đặt phòng (Dùng chuẩn snake_case)
            $table->date('check_in');       // Ngày đến
            $table->date('check_out');      // Ngày đi
            $table->integer('total_nights')->default(1);
            
            // 5. Tài chính & Trạng thái
            $table->decimal('total_price', 15, 2)->default(0);
            $table->string('status')->default('pending'); // pending, confirmed, cancelled
            
            // 6. Ghi chú
            $table->text('note')->nullable();
            
            // Timestamp (created_at, updated_at)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('bookings');
    }
};