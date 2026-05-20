<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->string('headline')->nullable()->after('title');
            $table->string('short_summary')->nullable()->after('description');
            $table->decimal('minimum_amount', 10, 2)->nullable()->after('target_amount');
            $table->boolean('allow_custom_amount')->default(true)->after('minimum_amount');
            $table->string('payment_gateway')->nullable()->after('allow_recurring');
            $table->text('thank_you_message')->nullable()->after('payment_gateway');
            $table->string('redirect_url')->nullable()->after('thank_you_message');
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn(['headline', 'short_summary', 'minimum_amount', 'allow_custom_amount', 'payment_gateway', 'thank_you_message', 'redirect_url']);
        });
    }
};
