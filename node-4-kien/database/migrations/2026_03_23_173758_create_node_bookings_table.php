<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('node_bookings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transaction_id'); 
            $table->string('node_port')->nullable(); 
            $table->unsignedBigInteger('room_id')->nullable();
            
            // THÊM MỚI: Cột lưu tên khách hàng hiển thị trên màn hình đen
            $table->string('customer_name')->nullable(); 
            
            $table->string('status')->default('pending'); 
            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('node_bookings');
    }
};