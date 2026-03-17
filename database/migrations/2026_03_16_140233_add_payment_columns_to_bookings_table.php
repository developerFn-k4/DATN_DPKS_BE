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
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('payment_method', 50)->nullable()->after('status');
            $table->enum('payment_status', ['unpaid', 'processing', 'paid', 'failed'])
                ->default('unpaid')
                ->after('payment_method');
            $table->string('payment_txn_ref', 64)->nullable()->unique()->after('total_price');
            $table->string('payment_transaction_no', 64)->nullable()->after('payment_txn_ref');
            $table->timestamp('paid_at')->nullable()->after('payment_transaction_no');
            $table->json('payment_response')->nullable()->after('paid_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'payment_method',
                'payment_status',
                'payment_txn_ref',
                'payment_transaction_no',
                'paid_at',
                'payment_response',
            ]);
        });
    }
};
