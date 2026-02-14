<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hotels', function (Blueprint $table) {
            $table->id(); // id (PK)

            $table->string('name');          // Tên khách sạn
            $table->text('address');         // Địa chỉ
            $table->string('phone');         // Hotline
            $table->string('email');         // Email quản trị
            $table->text('description')->nullable(); // Mô tả

            $table->time('check_in_time');   // Giờ check-in
            $table->time('check_out_time');  // Giờ check-out

            $table->enum('status', ['active', 'inactive'])
                  ->default('active');       // Trạng thái

            $table->timestamps(); // created_at, updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotels');
    }
};
