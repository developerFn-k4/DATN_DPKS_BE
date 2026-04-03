<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {

            // Xóa room_id
            $table->dropForeign(['room_id']);
            $table->dropColumn('room_id');

            // Thêm room_type_id
            $table->foreignId('room_type_id')
                ->after('id')
                ->constrained('room_types')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {

            $table->dropForeign(['room_type_id']);
            $table->dropColumn('room_type_id');

            $table->foreignId('room_id')
                ->after('id')
                ->constrained()
                ->cascadeOnDelete();
        });
    }
};
