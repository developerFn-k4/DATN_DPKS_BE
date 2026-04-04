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
            $table->decimal('extra_adult_price', 12, 2)
                ->default(0)
                ->after('max_babies');

            // Phụ thu trẻ em
            $table->decimal('extra_child_price', 12, 2)
                ->default(0)
                ->after('extra_adult_price');

            // Tổng số người tối đa cho phép
            $table->integer('max_occupancy')
                ->default(4)
                ->after('max_children');
        });
    }

    public function down(): void
    {
        Schema::table('room_types', function (Blueprint $table) {
            $table->dropColumn([
                'extra_adult_price',
                'extra_child_price',
                'max_occupancy'
            ]);
        });
    }
};
