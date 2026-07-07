<?php

use App\Models\Donation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->string('receipt_token', 64)->nullable()->after('public_id');
            $table->unique('receipt_token');
        });

        Donation::query()
            ->whereNull('receipt_token')
            ->chunkById(100, function ($donations) {
                foreach ($donations as $donation) {
                    $token = $this->generateUniqueToken();

                    Donation::query()
                        ->whereKey($donation->getKey())
                        ->update(['receipt_token' => $token]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->dropUnique(['receipt_token']);
            $table->dropColumn('receipt_token');
        });
    }

    private function generateUniqueToken(): string
    {
        do {
            $token = Str::random(32);
        } while (Donation::query()->where('receipt_token', $token)->exists());

        return $token;
    }
};
