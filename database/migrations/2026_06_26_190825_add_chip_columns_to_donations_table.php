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
        Schema::table('donations', function (Blueprint $table) {
            $table->string('chip_purchase_id')->nullable()->after('stripe_payment_intent_id');
            $table->string('chip_recurring_token')->nullable()->after('chip_purchase_id');
            $table->text('chip_checkout_url')->nullable()->after('chip_recurring_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->dropColumn(['chip_purchase_id', 'chip_recurring_token', 'chip_checkout_url']);
        });
    }
};
