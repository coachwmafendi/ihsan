<?php

use App\Actions\Chip\CancelRecurringToken;
use App\Actions\Chip\ChargeRecurringInstallment;
use App\Actions\Chip\ConfirmPurchase;
use App\Actions\Chip\RefundDonation;
use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Enums\PaymentGateway;
use App\Enums\SubscriptionInterval;
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

it('charges an active chip subscription and creates a renewal donation', function () {
    $baseUrl = config('services.chip.api_base_url');

    Http::fake([
        "{$baseUrl}/purchases/" => Http::response([
            'id' => 'renewal-purchase-001',
            'status' => 'preauthorized',
        ]),
        "{$baseUrl}/purchases/renewal-purchase-001/charge/" => Http::response([
            'id' => 'renewal-purchase-001',
            'status' => 'paid',
            'payment' => [
                'fee_amount' => 100,
                'net_amount' => 4900,
            ],
        ]),
    ]);

    $subscription = Subscription::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'chip_recurring_token' => 'token-purchase-001',
        'amount' => 50.00,
        'currency' => 'myr',
        'interval' => SubscriptionInterval::Monthly,
        'status' => SubscriptionStatus::Active,
        'payment_count' => 1,
        'next_charge_at' => now()->subDay(),
    ]);

    $previousNextCharge = $subscription->next_charge_at;

    $result = app(ChargeRecurringInstallment::class)->handle($subscription);

    $subscription->refresh();

    expect($result->succeeded())->toBeTrue()
        ->and($result->donation)->toBeInstanceOf(Donation::class)
        ->and($subscription->donations()->count())->toBe(1);

    $donation = $subscription->donations()->first();

    expect($donation->chip_purchase_id)->toBe('renewal-purchase-001')
        ->and($donation->status)->toBe(DonationStatus::Succeeded)
        ->and((float) $donation->gross_amount)->toEqual(50.00)
        ->and((float) $donation->stripe_fee)->toEqual(1.00)
        ->and((float) $donation->net_amount)->toEqual(49.00)
        ->and($subscription->payment_count)->toBe(2)
        ->and($subscription->next_charge_at)->not->toEqual($previousNextCharge)
        ->and($subscription->next_charge_at->isFuture())->toBeTrue();
});

it('cancels a chip subscription by deleting the recurring token', function () {
    $baseUrl = config('services.chip.api_base_url');

    Http::fake([
        "{$baseUrl}/purchases/token-123/delete_recurring_token/" => Http::response([], 200),
    ]);

    $subscription = Subscription::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'chip_recurring_token' => 'token-123',
        'amount' => 50.00,
        'currency' => 'myr',
        'interval' => SubscriptionInterval::Monthly,
        'status' => SubscriptionStatus::Active,
        'next_charge_at' => now()->addMonth(),
    ]);

    app(CancelRecurringToken::class)->cancel($subscription);

    $subscription->refresh();

    expect($subscription->status)->toBe(SubscriptionStatus::Cancelled)
        ->and($subscription->cancel_at_period_end)->toBeFalse()
        ->and($subscription->next_charge_at)->toBeNull();

    Http::assertSentCount(1);
    Http::assertSent(fn ($request) => $request->method() === 'POST' && str_contains($request->url(), '/purchases/token-123/delete_recurring_token/'));
});

it('refunds a chip donation and decrements campaign collected amount', function () {
    $baseUrl = config('services.chip.api_base_url');

    Http::fake([
        "{$baseUrl}/purchases/chip-purchase-001/refund/" => Http::response([
            'id' => 'refund-payment-001',
            'payment' => [
                'amount' => 5000,
                'currency' => 'MYR',
                'payment_type' => 'refund',
            ],
        ]),
    ]);

    $donation = Donation::factory()->state([
        'status' => DonationStatus::Succeeded->value,
        'type' => DonationType::OneTime->value,
    ])->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'chip_purchase_id' => 'chip-purchase-001',
        'gross_amount' => 50.00,
        'base_amount' => 50.00,
        'net_amount' => 50.00,
        'stripe_fee' => 0,
        'currency' => 'myr',
    ]);

    $this->campaign->update(['collected_amount' => 50.00]);

    app(RefundDonation::class)->handle($donation);

    $donation->refresh();
    $this->campaign->refresh();

    expect($donation->status)->toBe(DonationStatus::Refunded)
        ->and($donation->refunded_at)->not->toBeNull()
        ->and((float) $this->campaign->collected_amount)->toEqual(0.00);

    Http::assertSentCount(1);
    Http::assertSent(fn ($request) => $request->method() === 'POST' && str_contains($request->url(), '/purchases/chip-purchase-001/refund/'));
});
