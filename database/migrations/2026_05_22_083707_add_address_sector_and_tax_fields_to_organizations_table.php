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
            $table->string('address_line_1')->nullable()->after('logo_path');
            $table->string('address_line_2')->nullable()->after('address_line_1');
            $table->string('city')->nullable()->after('address_line_2');
            $table->string('state')->nullable()->after('city');
            $table->string('postcode', 20)->nullable()->after('state');
            $table->string('country', 100)->nullable()->default('Malaysia')->after('postcode');
            $table->string('sector')->nullable()->after('country');
            $table->boolean('tax_exempt')->default(false)->after('sector');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn([
                'address_line_1',
                'address_line_2',
                'city',
                'state',
                'postcode',
                'country',
                'sector',
                'tax_exempt',
            ]);
        });
    }
};
