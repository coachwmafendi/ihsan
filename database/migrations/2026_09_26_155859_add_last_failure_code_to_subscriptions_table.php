<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The bank's message says what happened; only the code says whether trying
     * again can help. A failed installment records no donation, so the code was
     * thrown away with the charge attempt and the notification could only
     * promise another retry, whatever the decline was.
     */
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->string('last_failure_code')->nullable()->after('last_failure_message');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->dropColumn('last_failure_code');
        });
    }
};
