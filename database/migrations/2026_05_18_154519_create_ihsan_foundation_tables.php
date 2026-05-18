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
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('ros_rob_number')->nullable()->unique();
            $table->string('registration_type')->default('others');
            $table->text('description')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('website_url')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('status')->default('pending')->index();
            $table->string('stripe_account_id')->nullable()->unique();
            $table->boolean('stripe_onboarded')->default(false);
            $table->string('bank_account_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_name')->nullable();
            $table->json('settings')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('organization_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('document_type');
            $table->string('file_path');
            $table->string('original_filename');
            $table->timestamps();
        });

        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();
            $table->decimal('target_amount', 12, 2)->nullable();
            $table->decimal('collected_amount', 12, 2)->default(0);
            $table->boolean('has_target')->default(false);
            $table->boolean('allow_recurring')->default(true);
            $table->date('end_date')->nullable();
            $table->string('status')->default('draft')->index();
            $table->json('suggested_amounts')->nullable();
            $table->timestamps();

            $table->index('organization_id');
        });

        Schema::create('donors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('stripe_customer_id')->nullable()->unique();
            $table->string('magic_token')->nullable()->index();
            $table->timestamp('magic_token_expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('donor_id')->constrained()->cascadeOnDelete();
            $table->string('stripe_subscription_id')->unique();
            $table->string('stripe_price_id')->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('currency')->default('myr');
            $table->string('interval');
            $table->string('status')->default('incomplete')->index();
            $table->unsignedTinyInteger('retry_count')->default(0);
            $table->timestamp('current_period_start')->nullable();
            $table->timestamp('current_period_end')->nullable();
            $table->timestamp('paused_until')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index('campaign_id');
            $table->index('donor_id');
        });

        Schema::create('donations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('donor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->string('stripe_payment_intent_id')->nullable()->unique();
            $table->string('stripe_charge_id')->nullable();
            $table->decimal('gross_amount', 12, 2);
            $table->decimal('stripe_fee', 12, 2)->default(0);
            $table->decimal('platform_fee', 12, 2)->default(0);
            $table->decimal('net_amount', 12, 2)->default(0);
            $table->string('currency')->default('myr');
            $table->string('status')->default('pending')->index();
            $table->string('type')->default('one_time')->index();
            $table->text('donor_message')->nullable();
            $table->boolean('is_anonymous')->default(false);
            $table->json('utm_params')->nullable();
            $table->timestamps();

            $table->index('campaign_id');
            $table->index('donor_id');
            $table->index('subscription_id');
            $table->index('created_at');
        });

        Schema::create('platform_fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('donation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->decimal('fee_amount', 12, 2);
            $table->decimal('fee_percentage', 5, 2);
            $table->string('stripe_transfer_id')->nullable();
            $table->string('status')->default('pending')->index();
            $table->timestamps();
        });

        Schema::create('elements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('token')->unique();
            $table->string('type');
            $table->json('config')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('organization_id');
            $table->index('campaign_id');
        });

        Schema::create('webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->string('stripe_event_id')->unique();
            $table->string('event_type')->index();
            $table->json('payload');
            $table->string('status')->default('received')->index();
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_logs');
        Schema::dropIfExists('elements');
        Schema::dropIfExists('platform_fees');
        Schema::dropIfExists('donations');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('donors');
        Schema::dropIfExists('campaigns');
        Schema::dropIfExists('organization_documents');
        Schema::dropIfExists('organizations');
    }
};
