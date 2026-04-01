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
        Schema::table('bookings', function (Blueprint $table) {
            
            // --- 1. SỬA LỖI QUAN TRỌNG: Bổ sung cột ngày tháng nếu thiếu ---
            if (!Schema::hasColumn('bookings', 'checkin')) {
                $table->date('checkin')->nullable()->after('id');
            }
            if (!Schema::hasColumn('bookings', 'checkout')) {
                // Đảm bảo có checkin rồi mới after('checkin')
                $table->date('checkout')->nullable()->after('checkin');
            }

            // --- 2. Thêm các cột mới (Có kiểm tra !hasColumn để tránh lỗi trùng) ---

            // Cột room_id (Foreign Key)
            if (!Schema::hasColumn('bookings', 'room_id')) {
                $table->foreignId('room_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('rooms')
                    ->onDelete('cascade');
            }

            // Cột total_nights
            if (!Schema::hasColumn('bookings', 'total_nights')) {
                // Lúc này chắc chắn đã có checkout nhờ bước 1
                $table->integer('total_nights')->default(1)->after('checkout');
            }

            // Cột total_price
            if (!Schema::hasColumn('bookings', 'total_price')) {
                $table->decimal('total_price', 12, 2)->default(0)->after('total_nights');
            }

            // Cột status
            if (!Schema::hasColumn('bookings', 'status')) {
                $table->enum('status', [
                    'pending',        // Chờ duyệt
                    'confirmed',      // Đã xác nhận
                    'checked_in',     // Đã check-in
                    'checked_out',    // Đã check-out
                    'cancelled',      // Đã hủy
                    'rejected'        // Bị từ chối
                ])->default('pending')->after('total_price');
            }

            // Cột admin_note
            if (!Schema::hasColumn('bookings', 'admin_note')) {
                $table->text('admin_note')->nullable()->after('status');
            }

            // Cột confirmed_by (Foreign Key)
            if (!Schema::hasColumn('bookings', 'confirmed_by')) {
                $table->foreignId('confirmed_by')
                    ->nullable()
                    ->constrained('users')
                    ->after('admin_note');
            }

            // Cột confirmed_at
            if (!Schema::hasColumn('bookings', 'confirmed_at')) {
                $table->timestamp('confirmed_at')->nullable()->after('confirmed_by');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Xóa khóa ngoại trước (kiểm tra để tránh lỗi nếu chưa tạo)
            // Lưu ý: Cần bọc trong try-catch hoặc kiểm tra kỹ hơn nếu chạy rollback, 
            // nhưng ở môi trường dev có thể chạy lệnh này:
            
            // Xóa cột (Laravel tự động xóa khóa ngoại đi kèm khi xóa cột foreignId)
            $columnsToDrop = [
                'room_id',
                'total_nights',
                'total_price',
                'status',
                'admin_note',
                'confirmed_by',
                'confirmed_at'
            ];

            // Chỉ xóa cột nếu nó tồn tại
            foreach ($columnsToDrop as $column) {
                if (Schema::hasColumn('bookings', $column)) {
                    // Nếu là cột khóa ngoại, cần drop foreign key trước (Laravel convention)
                    if (in_array($column, ['room_id', 'confirmed_by'])) {
                         $table->dropForeign([$column]);
                    }
                    $table->dropColumn($column);
                }
            }
            
            // Không xóa checkin/checkout vì có thể nó là cột gốc 
        });
    }
};