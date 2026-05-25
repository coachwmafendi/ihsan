<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->string('base_currency', 5)->nullable()->after('currency');
            $table->decimal('base_amount', 12, 2)->nullable()->after('base_currency');
        });

        // Backfill existing rows — all are MYR
        DB::table('donations')->whereNull('base_currency')->update([
            'base_currency' => 'myr',
            'base_amount' => DB::raw('gross_amount'),
        ]);
    }

    public function down(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->dropColumn(['base_currency', 'base_amount']);
        });
    }
};
