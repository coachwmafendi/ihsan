<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('donors', function (Blueprint $table) {
            if (! Schema::hasColumn('donors', 'first_name')) {
                $table->string('first_name')->nullable()->after('title');
            }

            if (! Schema::hasColumn('donors', 'last_name')) {
                $table->string('last_name')->nullable()->after('first_name');
            }
        });

        $this->backfillExistingDonors();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('donors', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'last_name']);
        });
    }

    /**
     * Best-effort split existing full names into first/last name fields.
     */
    private function backfillExistingDonors(): void
    {
        DB::table('donors')
            ->whereNull('first_name')
            ->whereNull('last_name')
            ->whereNotNull('name')
            ->orderBy('id')
            ->chunkById(500, function ($donors): void {
                foreach ($donors as $donor) {
                    $parts = explode(' ', trim((string) $donor->name), 2);

                    DB::table('donors')->where('id', $donor->id)->update([
                        'first_name' => $parts[0] ?? null,
                        'last_name' => $parts[1] ?? null,
                    ]);
                }
            });
    }
};
