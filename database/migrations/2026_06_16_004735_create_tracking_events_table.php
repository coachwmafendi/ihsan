<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracking_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('donation_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider', 50);
            $table->string('event_name', 100);
            $table->string('status', 20)->default('pending');
            $table->decimal('amount', 12, 2)->nullable();
            $table->char('currency', 3)->nullable();
            $table->string('campaign_name')->nullable();
            $table->json('payload')->nullable();
            $table->json('response')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'provider', 'created_at']);
            $table->index(['organization_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracking_events');
    }
};
