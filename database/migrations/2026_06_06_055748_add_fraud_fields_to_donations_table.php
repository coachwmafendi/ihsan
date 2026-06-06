<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->integer('risk_score')->nullable()->after('base_amount');
            $table->string('risk_level', 20)->nullable()->after('risk_score');
            $table->string('avs_result', 10)->nullable()->after('risk_level');
            $table->string('cvc_result', 10)->nullable()->after('avs_result');
            $table->string('fraud_status', 20)->default('clean')->after('cvc_result');
            $table->index('fraud_status');
        });
    }

    public function down(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->dropColumn(['risk_score', 'risk_level', 'avs_result', 'cvc_result', 'fraud_status']);
        });
    }
};
