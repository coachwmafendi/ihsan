<?php

use App\Actions\Chip\ConfirmPurchase;
use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Enums\PaymentGateway;
use App\Enums\SubscriptionStatus;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->organization = Organization::factory()->create([
        'settings' => [
            'chip_brand_id' => 'brand-test-uuid',
            'chip_api_key' => 'chip-test-key',
            'chip_payment_methods' => ['card'],
        ],
    ]);

    $this->campaign = Campaign::factory()->create([
        'organization_id' => $this->organization->id,
        'payment_gateway' => PaymentGateway::Chip,
        'campaign_page_enabled' => true,
        'checkout_modal_enabled' => true,
    ]);

    $this->donor = Donor::factory()->create([
        'first_name' => 'Ahmad',
        'last_name' => 'Tester',
        'email' => 'ahmad@example.com',
    ]);
});

it('creates a subscription on the first successful chip recurring payment', function () {
    Http::fake([
        'https://gate.chip-in.asia/api/v1/purchases/*' => Http::response([
            'id' => 'purchase-chip-001',
            'status' => 'paid',
            'payment' => [
                'fee_amount' => 125,
                'net_amount' => 4875,
            ],
        ]),
    ]);

    $donation = Donation::factory()->state([
        'status' => DonationStatus::Pending->value,
        'type' => DonationType::Recurring->value,
    ])->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'chip_purchase_id' => 'purchase-chip-001',
        'gross_amount' => 50.00,
        'base_amount' => 50.00,
        'net_amount' => 50.00,
        'stripe_fee' => 0,
        'currency' => 'myr',
    ]);

    app(ConfirmPurchase::class)->handle($donation);

    $donation->refresh();

    expect($donation->status)->toBe(DonationStatus::Succeeded)
        ->and($donation->subscription_id)->not->toBeNull()
        ->and($donation->subscription)->toBeInstanceOf(Subscription::class)
        ->and($donation->subscription->chip_recurring_token)->toBe('purchase-chip-001')
        ->and($donation->subscription->status)->toBe(SubscriptionStatus::Active)
        ->and((float) $donation->gross_amount)->toEqual(50.00)
        ->and((float) $donation->subscription->amount)->toEqual(50.00);
});
