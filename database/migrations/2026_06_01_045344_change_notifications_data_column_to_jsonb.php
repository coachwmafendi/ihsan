<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE notifications ALTER COLUMN data TYPE JSON USING data::json');
        } elseif ($driver === 'mysql') {
            DB::statement('ALTER TABLE notifications MODIFY COLUMN data JSON');
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE notifications ALTER COLUMN data TYPE TEXT');
        } elseif ($driver === 'mysql') {
            DB::statement('ALTER TABLE notifications MODIFY COLUMN data TEXT');
        }
    }
};
