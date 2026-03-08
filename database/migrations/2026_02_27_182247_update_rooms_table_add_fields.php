<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {

            // Thêm trạng thái mới
            $table->enum('status', [
                'available',
                'cleaning',
                'maintenance',
                'inactive'
            ])->default('available')->change();

            // Thêm cột note
            $table->text('note')->nullable()->after('status');

            // Thêm soft delete
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn('note');
            $table->dropSoftDeletes();

            $table->enum('status', [
                'available',
                'maintenance'
            ])->default('available')->change();
        });
    }
};
