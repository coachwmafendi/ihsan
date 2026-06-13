<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('title');
        });

        $slugs = [];
        $campaigns = DB::table('campaigns')->whereNull('slug')->get(['id', 'title']);

        foreach ($campaigns as $campaign) {
            $base = Str::slug($campaign->title ?: 'campaign');
            $slug = $base;
            $counter = 1;

            while (in_array($slug, $slugs, true) || DB::table('campaigns')->where('slug', $slug)->exists()) {
                $slug = $base.'-'.$counter++;
            }

            $slugs[] = $slug;
            DB::table('campaigns')->where('id', $campaign->id)->update(['slug' => $slug]);
        }

        Schema::table('campaigns', function (Blueprint $table) {
            $table->unique('slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
