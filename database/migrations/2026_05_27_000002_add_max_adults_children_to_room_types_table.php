<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('room_types', function (Blueprint $table) {
            if (!Schema::hasColumn('room_types', 'max_adults')) {
                $table->integer('max_adults')->default(2)->after('capacity');
            }
            if (!Schema::hasColumn('room_types', 'max_children')) {
                $table->integer('max_children')->default(0)->after('max_adults');
            }
        });
    }

    public function down(): void
    {
        Schema::table('room_types', function (Blueprint $table) {
            if (Schema::hasColumn('room_types', 'max_adults')) {
                $table->dropColumn('max_adults');
            }
            if (Schema::hasColumn('room_types', 'max_children')) {
                $table->dropColumn('max_children');
            }
        });
    }
};
