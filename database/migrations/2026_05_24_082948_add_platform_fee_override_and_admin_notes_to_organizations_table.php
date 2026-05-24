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
        Schema::table('organizations', function (Blueprint $table) {
            $table->decimal('platform_fee_override', 5, 2)->nullable()->after('tax_exempt');
            $table->text('admin_notes')->nullable()->after('platform_fee_override');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn(['platform_fee_override', 'admin_notes']);
        });
    }
};
