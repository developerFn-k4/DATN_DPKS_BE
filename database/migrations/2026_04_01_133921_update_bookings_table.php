<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (Schema::hasColumn('bookings', 'payment_method')) {
                $table->dropColumn('payment_method');
            }
            if (!Schema::hasColumn('bookings', 'booking_code')) {
                $table->string('booking_code')->unique()->after('id');
            }
            if (!Schema::hasColumn('bookings', 'nights')) {
                $table->integer('nights')->after('check_out');
            }
            if (!Schema::hasColumn('bookings', 'source')) {
                $table->enum('source', [
                    'website',
                    'admin',
                    'ota',
                    'walkin'
                ])->default('website')->after('status');
            }
            if (!Schema::hasColumn('bookings', 'special_request')) {
                $table->text('special_request')->nullable()->after('guests');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'payment_method')) {
                $table->enum('payment_method', ['momo', 'vnpay', 'cash', 'bank_transfer'])->nullable();
            }
            $cols = array_filter(['booking_code', 'nights', 'source', 'special_request'], fn($c) => Schema::hasColumn('bookings', $c));
            if ($cols) $table->dropColumn(array_values($cols));
        });
    }
};
