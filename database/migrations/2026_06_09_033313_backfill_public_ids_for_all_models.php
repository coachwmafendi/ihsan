<?php

use App\Models\Campaign;
use App\Models\Donor;
use App\Models\Element;
use App\Services\PublicIdGenerator;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([Campaign::class, Donor::class, Element::class] as $modelClass) {
            $modelClass::whereNull('public_id')->chunkById(100, function ($records) use ($modelClass) {
                foreach ($records as $record) {
                    $record->public_id = PublicIdGenerator::generate($modelClass);
                    $record->saveQuietly();
                }
            });
        }
    }

    public function down(): void
    {
        //
    }
};
