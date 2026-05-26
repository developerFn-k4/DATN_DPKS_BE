<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'payment_method')) {
                $table->enum('payment_method', [
                    'momo',
                    'vnpay',
                    'cash',
                    'bank_transfer'
                ])->nullable()->after('total_price');
            }
        });
    }

    public function down()
    {
        Schema::table('bookings', function (Blueprint $table) {

             if (Schema::hasColumn('bookings', 'payment_method')) {
                $table->dropColumn('payment_method');
            }
        });
    }
};
