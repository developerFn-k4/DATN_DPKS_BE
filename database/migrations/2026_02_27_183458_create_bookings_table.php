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
        Schema::create('bookings', function (Blueprint $table) {

            $table->id();
            // Khóa chính tự tăng

            $table->foreignId('room_id')
                ->constrained()
                ->cascadeOnDelete();
            /*
             room_id liên kết với bảng rooms
             Nếu phòng bị xóa (soft delete thì không ảnh hưởng),
             nếu xóa cứng thì booking cũng bị xóa theo
            */

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();
            /*
             user đặt phòng
             Sau này nếu dùng Sanctum thì lấy từ auth()->id()
            */

            $table->date('check_in');
            // Ngày nhận phòng

            $table->date('check_out');
            // Ngày trả phòng

            $table->unsignedInteger('guests');
            // Số lượng khách ở

            $table->enum('status', [
                'pending',     // Chờ xác nhận
                'confirmed',   // Đã xác nhận
                'cancelled',   // Đã hủy
                'completed'    // Đã hoàn thành
            ])->default('pending');

            $table->decimal('total_price', 12, 2)->nullable();
            /*
             12 tổng số chữ số
             2 số sau dấu thập phân
             Ví dụ: 1500000.00
            */

            $table->timestamps();
            // created_at, updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
