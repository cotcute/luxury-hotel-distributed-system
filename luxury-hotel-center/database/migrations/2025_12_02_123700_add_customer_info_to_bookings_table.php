<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {   
        Schema::table('bookings', function (Blueprint $table) {
            // Kiểm tra nếu chưa có cột 'note' thì thêm vào
            if (!Schema::hasColumn('bookings', 'note')) {
                // Đặt cột note nằm sau cột checkout cho hợp lý
                $table->text('note')->nullable()->after('checkout');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (Schema::hasColumn('bookings', 'note')) {
                $table->dropColumn('note');
            }
        });
    }
};