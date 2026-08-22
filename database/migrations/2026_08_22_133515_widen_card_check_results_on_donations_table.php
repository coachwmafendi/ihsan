<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Stripe reports card checks as "pass", "fail", "unchecked" or
     * "unavailable". The last is 11 characters and did not fit the original
     * varchar(10), so Postgres rejected the whole update and the webhook job
     * failed — one payment's risk details were lost with it.
     */
    public function up(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->string('avs_result', 20)->nullable()->change();
            $table->string('cvc_result', 20)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->string('avs_result', 10)->nullable()->change();
            $table->string('cvc_result', 10)->nullable()->change();
        });
    }
};
