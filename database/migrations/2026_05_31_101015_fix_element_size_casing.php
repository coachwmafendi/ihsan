<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('elements')
            ->where('config', 'like', '%"size":"small"%')
            ->update([
                'config' => DB::raw("REPLACE(config, '\"size\":\"small\"', '\"size\":\"Small\"')"),
            ]);

        DB::table('elements')
            ->where('config', 'like', '%"size":"medium"%')
            ->update([
                'config' => DB::raw("REPLACE(config, '\"size\":\"medium\"', '\"size\":\"Medium\"')"),
            ]);

        DB::table('elements')
            ->where('config', 'like', '%"size":"large"%')
            ->update([
                'config' => DB::raw("REPLACE(config, '\"size\":\"large\"', '\"size\":\"Large\"')"),
            ]);
    }

    public function down(): void
    {
        DB::table('elements')
            ->where('config', 'like', '%"size\":\"Small\"%')
            ->update([
                'config' => DB::raw("REPLACE(config, '\"size\":\"Small\"', '\"size\":\"small\"')"),
            ]);

        DB::table('elements')
            ->where('config', 'like', '%"size\":\"Medium\"%')
            ->update([
                'config' => DB::raw("REPLACE(config, '\"size\":\"Medium\"', '\"size\":\"medium\"')"),
            ]);

        DB::table('elements')
            ->where('config', 'like', '%"size\":\"Large\"%')
            ->update([
                'config' => DB::raw("REPLACE(config, '\"size\":\"Large\"', '\"size\":\"large\"')"),
            ]);
    }
};
