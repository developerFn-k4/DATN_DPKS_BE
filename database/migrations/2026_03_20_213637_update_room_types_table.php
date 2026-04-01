<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('room_types', function (Blueprint $table) {

            // Xóa description
            $table->dropColumn('description');

            // Diện tích phòng (m2)
            $table->integer('area')->after('bed_type');

            // Tiện ích phòng
            $table->json('amenities')->nullable()->after('area');
        });
    }

    public function down(): void
    {
        Schema::table('room_types', function (Blueprint $table) {

            $table->text('description')->nullable();

            $table->dropColumn('area');

            $table->dropColumn('amenities');
        });
    }
};
