<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->timestamp('receipt_sent_at')->nullable()->after('exchange_rate');
            $table->string('payment_method_last4', 4)->nullable()->after('receipt_sent_at');
            $table->string('billing_address_line1')->nullable()->after('payment_method_last4');
            $table->string('billing_address_line2')->nullable()->after('billing_address_line1');
            $table->string('billing_address_city')->nullable()->after('billing_address_line2');
            $table->string('billing_address_state')->nullable()->after('billing_address_city');
            $table->string('billing_address_postal_code')->nullable()->after('billing_address_state');
            $table->string('billing_country', 2)->nullable()->after('billing_address_postal_code');
        });
    }

    public function down(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->dropColumn([
                'receipt_sent_at', 'payment_method_last4',
                'billing_address_line1', 'billing_address_line2',
                'billing_address_city', 'billing_address_state',
                'billing_address_postal_code', 'billing_country',
            ]);
        });
    }
};
