<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE bookings MODIFY COLUMN status ENUM('pending','confirmed','checked_in','checked_out','cancelled','completed') NOT NULL DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Map new statuses back to legacy ones before shrinking enum values.
        DB::statement("UPDATE bookings SET status = 'confirmed' WHERE status = 'checked_in'");
        DB::statement("UPDATE bookings SET status = 'completed' WHERE status = 'checked_out'");

        DB::statement("ALTER TABLE bookings MODIFY COLUMN status ENUM('pending','confirmed','cancelled','completed') NOT NULL DEFAULT 'pending'");
    }
<<<<<<< HEAD
};
=======
};
>>>>>>> c3d6c77a71b885d7a9c9a919d3aa4b1a8a5127a1
