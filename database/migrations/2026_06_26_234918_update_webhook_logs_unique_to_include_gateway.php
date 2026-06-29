<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasIndex('webhook_logs', 'webhook_logs_stripe_event_id_unique')) {
            Schema::table('webhook_logs', function (Blueprint $table) {
                $table->dropUnique(['stripe_event_id']);
            });
        }

        if (! Schema::hasIndex('webhook_logs', 'webhook_logs_gateway_stripe_event_id_unique')) {
            Schema::table('webhook_logs', function (Blueprint $table) {
                $table->unique(['gateway', 'stripe_event_id']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex('webhook_logs', 'webhook_logs_gateway_stripe_event_id_unique')) {
            Schema::table('webhook_logs', function (Blueprint $table) {
                $table->dropUnique(['gateway', 'stripe_event_id']);
            });
        }

        if (! Schema::hasIndex('webhook_logs', 'webhook_logs_stripe_event_id_unique')) {
            Schema::table('webhook_logs', function (Blueprint $table) {
                $table->unique(['stripe_event_id']);
            });
        }
    }
};
