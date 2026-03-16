<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {

            $table->string('phone', 20)->nullable()->after('email');

            $table->string('address')->nullable()->after('phone');

            $table->date('date_of_birth')->nullable()->after('address');

            $table->enum('status', ['active', 'blocked'])
                ->default('active')
                ->after('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {

            $table->dropColumn([
                'phone',
                'address',
                'date_of_birth',
                'status'
            ]);
        });
    }
};
