<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropUnique('organizations_slug_unique');
            $table->dropColumn('slug');
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->string('code', 8)->after('id')->nullable();
        });

        DB::table('organizations')->orderBy('id')->each(function ($organization) {
            DB::table('organizations')
                ->where('id', $organization->id)
                ->update(['code' => strtoupper(Str::random(8))]);
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->string('code', 8)->unique()->change();
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropUnique('organizations_code_unique');
            $table->dropColumn('code');
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->string('slug')->after('name')->nullable();
        });

        DB::table('organizations')->orderBy('id')->each(function ($organization) {
            DB::table('organizations')
                ->where('id', $organization->id)
                ->update(['slug' => str($organization->name)->slug()]);
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->string('slug')->unique()->change();
        });
    }
};
