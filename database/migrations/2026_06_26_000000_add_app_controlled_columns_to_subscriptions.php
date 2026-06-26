<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->foreignId('donor_payment_method_id')
                ->nullable()
                ->after('donor_id')
                ->constrained('donor_payment_methods')
                ->nullOnDelete();
            $table->timestamp('next_charge_at')->nullable()->after('current_period_end');
            $table->timestamp('last_charge_at')->nullable()->after('next_charge_at');
            $table->timestamp('last_charge_attempt_at')->nullable()->after('last_charge_at');
            $table->unsignedInteger('failed_installment_count')->default(0)->after('retry_count');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->dropForeign(['donor_payment_method_id']);
            $table->dropColumn([
                'donor_payment_method_id',
                'next_charge_at',
                'last_charge_at',
                'last_charge_attempt_at',
                'failed_installment_count',
            ]);
        });
    }
};
