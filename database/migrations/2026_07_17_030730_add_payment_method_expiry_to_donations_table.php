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
            $table->unsignedTinyInteger('payment_method_exp_month')->nullable()->after('payment_method_last4');
            $table->unsignedSmallInteger('payment_method_exp_year')->nullable()->after('payment_method_exp_month');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->dropColumn(['payment_method_exp_month', 'payment_method_exp_year']);
        });
    }
};
