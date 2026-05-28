<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('donors', function (Blueprint $table) {
            $table->string('address_line1')->nullable()->after('phone');
            $table->string('address_line2')->nullable()->after('address_line1');
            $table->string('address_city')->nullable()->after('address_line2');
            $table->string('address_state')->nullable()->after('address_city');
            $table->string('address_postal_code')->nullable()->after('address_state');
            $table->string('country', 2)->nullable()->after('address_postal_code');
            $table->string('locale', 5)->nullable()->after('country');
        });
    }

    public function down(): void
    {
        Schema::table('donors', function (Blueprint $table) {
            $table->dropColumn([
                'address_line1', 'address_line2', 'address_city',
                'address_state', 'address_postal_code', 'country', 'locale',
            ]);
        });
    }
};
