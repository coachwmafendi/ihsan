<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->renameColumn('platform_fee', 'processing_fee');
        });

        Schema::rename('platform_fees', 'processing_fees');
    }

    public function down(): void
    {
        Schema::rename('processing_fees', 'platform_fees');

        Schema::table('donations', function (Blueprint $table) {
            $table->renameColumn('processing_fee', 'platform_fee');
        });
    }
};
