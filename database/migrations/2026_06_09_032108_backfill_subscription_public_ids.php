<?php

use App\Models\Subscription;
use App\Services\PublicIdGenerator;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Subscription::whereNull('public_id')->chunkById(100, function ($subscriptions) {
            foreach ($subscriptions as $subscription) {
                $subscription->public_id = PublicIdGenerator::generate(Subscription::class);
                $subscription->saveQuietly();
            }
        });
    }

    public function down(): void
    {
        //
    }
};
