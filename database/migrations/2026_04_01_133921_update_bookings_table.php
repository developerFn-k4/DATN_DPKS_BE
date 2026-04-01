<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {

            // remove column
            $table->dropColumn('payment_method');

            // new columns
            $table->string('booking_code')->unique()->after('id');

            $table->integer('nights')->after('check_out');

            $table->enum('source', [
                'website',
                'admin',
                'ota',
                'walkin'
            ])->default('website')->after('status');

            $table->text('special_request')->nullable()->after('guests');

            $table->timestamp('paid_at')->nullable()->after('expired_at');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {

            $table->enum('payment_method', [
                'momo',
                'vnpay',
                'cash',
                'bank_transfer'
            ])->nullable();

            $table->dropColumn([
                'booking_code',
                'nights',
                'source',
                'special_request',
                'paid_at'
            ]);
        });
    }
};
