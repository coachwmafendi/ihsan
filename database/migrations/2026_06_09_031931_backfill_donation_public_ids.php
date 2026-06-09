<?php

use App\Models\Donation;
use App\Services\PublicIdGenerator;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Donation::whereNull('public_id')->chunkById(100, function ($donations) {
            foreach ($donations as $donation) {
                $donation->public_id = PublicIdGenerator::generate(Donation::class);
                $donation->saveQuietly();
            }
        });
    }

    public function down(): void
    {
        //
    }
};
