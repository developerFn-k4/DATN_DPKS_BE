<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->enum('status', [
                'available',   // Còn trống
                'booked',      // Đã đặt
                'occupied',    // Đang sử dụng
                'maintenance', // Bảo trì
                'reserved',    // Giữ chỗ
                'unavailable'  // Không khả dụng
            ])->default('available')->change();
        });
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->enum('status', [
                'available',
                'cleaning',
                'maintenance',
                'inactive'
            ])->default('available')->change();
        });
    }
};
