<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->string('source', 50)->nullable()->after('type');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('source', 50)->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->dropColumn('source');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
