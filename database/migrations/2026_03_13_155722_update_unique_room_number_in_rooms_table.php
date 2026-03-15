<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('rooms', function (Blueprint $table) {

            // Xóa unique cũ
            $table->dropUnique('rooms_room_number_unique');

            // Tạo unique mới theo cặp
            $table->unique(['room_number', 'deleted_at']);
        });
    }

    public function down()
    {
        Schema::table('rooms', function (Blueprint $table) {

            // Xóa unique theo cặp
            $table->dropUnique(['room_number', 'deleted_at']);

            // Khôi phục unique cũ
            $table->unique('room_number');
        });
    }
};
