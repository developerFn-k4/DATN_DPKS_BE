<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('room_types', function (Blueprint $table) {
            $table->integer('max_babies')->default(0)->after('max_children');
        });
    }

    public function down()
    {
        Schema::table('room_types', function (Blueprint $table) {
            $table->dropColumn('max_babies');
        });
    }
};
