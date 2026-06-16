<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracking_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 50);
            $table->boolean('is_enabled')->default(false);
            $table->text('credentials')->nullable();
            $table->json('options')->nullable();
            $table->string('status', 20)->default('not_configured');
            $table->text('error_message')->nullable();
            $table->timestamp('last_tested_at')->nullable();
            $table->timestamp('last_event_at')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'provider']);
            $table->index(['organization_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracking_configurations');
    }
};
