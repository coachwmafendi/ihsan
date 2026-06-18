<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('donor_email_logs', function (Blueprint $table) {
            $table->string('message_id', 36)->nullable()->unique()->after('mailable_class');
            $table->string('provider_message_id')->nullable()->index()->after('message_id');
            $table->string('delivery_status', 20)->default('queued')->after('subject');
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('bounced_at')->nullable();
            $table->string('bounce_reason')->nullable();
            $table->timestamp('complained_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('donor_email_logs', function (Blueprint $table) {
            $table->dropColumn([
                'message_id',
                'provider_message_id',
                'delivery_status',
                'delivered_at',
                'bounced_at',
                'bounce_reason',
                'complained_at',
            ]);
        });
    }
};
