<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // This migration duplicates an earlier creation of room_types.
        // Keep it as a no-op to preserve migration history without conflicts.
        if (Schema::hasTable('room_types')) {
            return;
        }

        Schema::create('room_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('capacity');
            $table->string('bed_type');
            $table->decimal('base_price', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // No-op: room_types is managed by an earlier migration.
    }
};
