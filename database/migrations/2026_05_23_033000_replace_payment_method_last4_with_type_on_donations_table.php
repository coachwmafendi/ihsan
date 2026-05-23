<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->string('payment_method_type', 50)->nullable()->after('payment_method_brand');
        });

        Schema::table('donations', function (Blueprint $table) {
            $table->dropColumn('payment_method_last4');
        });
    }

    public function down(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->string('payment_method_last4', 10)->nullable()->after('payment_method_brand');
        });

        Schema::table('donations', function (Blueprint $table) {
            $table->dropColumn('payment_method_type');
        });
    }
};
