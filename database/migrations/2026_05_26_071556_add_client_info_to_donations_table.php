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
            $table->ipAddress('ip_address')->nullable()->after('device_type');
            $table->string('browser', 50)->nullable()->after('ip_address');
            $table->string('os', 50)->nullable()->after('browser');
            $table->string('page_url', 500)->nullable()->after('os');
            $table->string('geo_city', 100)->nullable()->after('page_url');
            $table->string('geo_region', 100)->nullable()->after('geo_city');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->dropColumn([
                'ip_address',
                'browser',
                'os',
                'page_url',
                'geo_city',
                'geo_region',
            ]);
        });
    }
};
