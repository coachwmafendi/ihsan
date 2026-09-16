<?php

use App\Actions\Stripe\NormalizeDonorDefaultPaymentMethods;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(NormalizeDonorDefaultPaymentMethods::class)->run();
    }

    /**
     * Nothing to reverse — the cleared flags were wrong to begin with.
     */
    public function down(): void {}
};
