<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * SES identifies a message by its own id, and we never kept one. The only
     * way an event could find its log was a custom header, so an event that
     * arrived without the original headers had nothing to match on and was
     * dropped: one email in twenty never recorded its delivery.
     *
     * provider_message_id cannot serve, because it holds the SMTP Message-ID
     * the mailer generated, which is a different identifier entirely.
     */
    public function up(): void
    {
        Schema::table('donor_email_logs', function (Blueprint $table): void {
            $table->string('ses_message_id')->nullable()->after('provider_message_id');
            $table->index('ses_message_id');
        });
    }

    public function down(): void
    {
        Schema::table('donor_email_logs', function (Blueprint $table): void {
            $table->dropIndex(['ses_message_id']);
            $table->dropColumn('ses_message_id');
        });
    }
};
