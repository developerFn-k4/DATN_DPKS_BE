<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('room_types', function (Blueprint $table) {
            $table->id();

            $table->foreignId('hotel_id')
                  ->constrained('hotels')
                  ->cascadeOnDelete();

            $table->string('name');                    // Tên loại phòng
            $table->text('description')->nullable();  // Mô tả
            $table->integer('capacity');               // Số người tối đa
            $table->string('bed_type');                // Loại giường
            $table->decimal('base_price', 12, 2);      // Giá / đêm
            $table->string('currency', 3);             // VND / USD
            $table->enum('status', ['active', 'inactive'])
                  ->default('active');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_types');
    }
};

