<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE bookings MODIFY COLUMN status ENUM('pending','confirmed','checked_in','checked_out','cancelled','completed') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("UPDATE bookings SET status = 'confirmed' WHERE status = 'checked_in'");
        DB::statement("UPDATE bookings SET status = 'completed' WHERE status = 'checked_out'");

        DB::statement("ALTER TABLE bookings MODIFY COLUMN status ENUM('pending','confirmed','cancelled','completed') NOT NULL DEFAULT 'pending'");
    }
};
