<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->boolean('checkout_modal_enabled')->default(false)->after('form_parameter');
            $table->json('checkout_allowed_domains')->nullable()->after('checkout_modal_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn(['checkout_modal_enabled', 'checkout_allowed_domains']);
        });
    }
};
