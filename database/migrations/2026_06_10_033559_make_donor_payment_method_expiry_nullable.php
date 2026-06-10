<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('donor_payment_methods', function (Blueprint $table) {
            $table->tinyInteger('exp_month')->nullable()->change();
            $table->smallInteger('exp_year')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('donor_payment_methods', function (Blueprint $table) {
            $table->tinyInteger('exp_month')->nullable(false)->change();
            $table->smallInteger('exp_year')->nullable(false)->change();
        });
    }
};
