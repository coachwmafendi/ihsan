<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Stripe customers live inside a single connected account, so a donor who
     * supports several organizations needs one customer per account.
     */
    public function up(): void
    {
        Schema::create('donor_stripe_customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('donor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('stripe_customer_id');
            $table->timestamps();

            $table->unique(['donor_id', 'organization_id']);
            $table->unique('stripe_customer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('donor_stripe_customers');
    }
};
