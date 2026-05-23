<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monthly_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('stripe_invoice_id')->unique();
            $table->string('invoice_number')->unique();
            $table->date('period');
            $table->decimal('total_fees', 12, 2);
            $table->string('stripe_status')->default('open')->index();
            $table->timestamp('paid_at')->nullable();
            $table->string('stripe_invoice_url')->nullable();
            $table->string('stripe_invoice_pdf')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_invoices');
    }
};
