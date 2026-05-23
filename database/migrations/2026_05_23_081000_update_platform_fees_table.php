<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_fees', function (Blueprint $table) {
            $table->foreignId('monthly_invoice_id')->nullable()->constrained()->nullOnDelete()->index()->after('status');
        });

        DB::statement("UPDATE platform_fees SET status = 'paid' WHERE status = 'transferred'");
    }

    public function down(): void
    {
        Schema::table('platform_fees', function (Blueprint $table) {
            $table->dropConstrainedForeignId('monthly_invoice_id');
        });
    }
};
