<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'name')) {
                $table->string('name')->after('user_id');
            }
            if (!Schema::hasColumn('bookings', 'email')) {
                $table->string('email')->after('name');
            }
            if (!Schema::hasColumn('bookings', 'phone')) {
                $table->string('phone')->after('email');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $cols = array_filter(['name', 'email', 'phone'], fn($c) => Schema::hasColumn('bookings', $c));
            if ($cols) $table->dropColumn(array_values($cols));
        });
    }
};
