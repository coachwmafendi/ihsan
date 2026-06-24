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
            $table->timestamp('new_donation_notification_sent_at')->nullable()->after('receipt_sent_at');
            $table->timestamp('large_donation_notification_sent_at')->nullable()->after('new_donation_notification_sent_at');

            $table->index('new_donation_notification_sent_at');
            $table->index('large_donation_notification_sent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->dropIndex(['new_donation_notification_sent_at']);
            $table->dropIndex(['large_donation_notification_sent_at']);
            $table->dropColumn(['new_donation_notification_sent_at', 'large_donation_notification_sent_at']);
        });
    }
};
