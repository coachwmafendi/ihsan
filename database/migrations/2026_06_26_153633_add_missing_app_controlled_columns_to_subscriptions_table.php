<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table): void {
            if (! Schema::hasColumn('subscriptions', 'donor_payment_method_id')) {
                $table->foreignId('donor_payment_method_id')
                    ->nullable()
                    ->after('donor_id')
                    ->constrained('donor_payment_methods')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('subscriptions', 'next_charge_at')) {
                $table->timestamp('next_charge_at')->nullable()->after('current_period_end');
            }

            if (! Schema::hasColumn('subscriptions', 'last_charge_at')) {
                $table->timestamp('last_charge_at')->nullable()->after('next_charge_at');
            }

            if (! Schema::hasColumn('subscriptions', 'last_charge_attempt_at')) {
                $table->timestamp('last_charge_attempt_at')->nullable()->after('last_charge_at');
            }

            if (! Schema::hasColumn('subscriptions', 'failed_installment_count')) {
                $table->unsignedInteger('failed_installment_count')->default(0)->after('retry_count');
            }

            DB::statement('CREATE INDEX IF NOT EXISTS subscriptions_charge_due_index ON subscriptions (stripe_subscription_id, status, next_charge_at)');
        });
    }

    /**
     * Reverse the migrations.
     */
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

        DB::statement('DROP INDEX IF EXISTS subscriptions_charge_due_index');
    }
};
