<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Cập nhật dữ liệu cũ để phù hợp ENUM mới
        DB::table('payments')->whereNotIn('status', ['pending', 'success', 'failed', 'canceled'])
            ->update(['status' => 'pending']);

        // 2. Đổi kiểu cột status sang ENUM
        Schema::table('payments', function (Blueprint $table) {
            $table->enum('status', ['pending', 'success', 'failed', 'canceled'])
                ->default('pending')
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Quay lại varchar(255) nếu rollback
            $table->string('status', 255)->default('pending')->change();
        });
    }
};
