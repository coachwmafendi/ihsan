<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('donor_payment_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('donor_id')->constrained()->onDelete('cascade');
            $table->string('stripe_payment_method_id')->unique();
            $table->string('brand', 50);
            $table->string('last4', 4);
            $table->tinyInteger('exp_month');
            $table->smallInteger('exp_year');
            $table->string('country', 2)->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['donor_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donor_payment_methods');
    }
};
