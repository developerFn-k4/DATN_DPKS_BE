<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('room_types', function (Blueprint $table) {

              if (Schema::hasColumn('room_types', 'description')) {
                $table->dropColumn('description');
            }
            if (!Schema::hasColumn('room_types', 'area')) {
                $table->integer('area')->after('bed_type');
            }
            if (!Schema::hasColumn('room_types', 'amenities')) {
                $table->json('amenities')->nullable()->after('area');
            }
        });
    }

    public function down(): void
    {
        Schema::table('room_types', function (Blueprint $table) {

            if (!Schema::hasColumn('room_types', 'description')) {
                $table->text('description')->nullable();
            }
            if (Schema::hasColumn('room_types', 'area')) {
                $table->dropColumn('area');
            }
            if (Schema::hasColumn('room_types', 'amenities')) {
                $table->dropColumn('amenities');
            }
        });
    }
};
