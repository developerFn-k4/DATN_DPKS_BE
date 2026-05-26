<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('room_types', function (Blueprint $table) {
            // Phụ thu người lớn
             if (!Schema::hasColumn('room_types', 'extra_adult_price')) {
                $table->decimal('extra_adult_price', 12, 2)->default(0)->after('max_babies');
            }
            if (!Schema::hasColumn('room_types', 'extra_child_price')) {
                $table->decimal('extra_child_price', 12, 2)->default(0)->after('extra_adult_price');
            }
            if (!Schema::hasColumn('room_types', 'max_occupancy')) {
                $table->integer('max_occupancy')->default(4)->after('max_children');
            }
        });
    }

    public function down(): void
    {
        Schema::table('room_types', function (Blueprint $table) {
            $cols = array_filter(['extra_adult_price', 'extra_child_price', 'max_occupancy'], fn($c) => Schema::hasColumn('room_types', $c));
            if ($cols) $table->dropColumn(array_values($cols));
        });
    }
};
