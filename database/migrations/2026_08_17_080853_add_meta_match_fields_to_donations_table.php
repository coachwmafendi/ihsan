<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('donations', function (Blueprint $table): void {
            $table->string('user_agent', 512)->nullable()->after('browser');
            $table->string('fbp')->nullable()->after('user_agent');
            $table->string('fbc', 512)->nullable()->after('fbp');
        });
    }

    public function down(): void
    {
        Schema::table('donations', function (Blueprint $table): void {
            $table->dropColumn(['user_agent', 'fbp', 'fbc']);
        });
    }
};
