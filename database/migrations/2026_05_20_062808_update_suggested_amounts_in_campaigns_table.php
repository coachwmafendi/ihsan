<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->json('suggested_amounts_one_time')->nullable()->after('suggested_amounts');
            $table->json('suggested_amounts_monthly')->nullable()->after('suggested_amounts_one_time');
            $table->boolean('impact_descriptions_enabled')->default(false)->after('suggested_amounts_monthly');
            $table->decimal('default_monthly_amount', 10, 2)->nullable()->after('impact_descriptions_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn(['suggested_amounts_one_time', 'suggested_amounts_monthly', 'impact_descriptions_enabled', 'default_monthly_amount']);
        });
    }
};
