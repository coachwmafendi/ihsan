<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->string('stripe_invoice_id', 100)->nullable()->after('stripe_charge_id');
            $table->index('stripe_invoice_id', 'donations_stripe_invoice_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->dropIndex('donations_stripe_invoice_id_index');
            $table->dropColumn('stripe_invoice_id');
        });
    }
};
