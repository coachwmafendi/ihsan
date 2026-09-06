<?php

use App\Actions\Chip\ChipApiFactory;
use App\Actions\Chip\FinalizeDonation;
use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Jobs\ProcessChipWebhook;
use App\Jobs\SendDonationReceipt;
use App\Jobs\SendMetaConversionEvent;
use App\Jobs\SendNewDonationNotification;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Organization;
use App\Models\Subscription;
use Chip\ChipApi;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Queue;

beforeEach(fn () => ChipApiFactory::resetFake());

afterEach(fn () => ChipApiFactory::resetFake());

function fakeChipPurchaseResponse(string $purchaseId, string $status = 'paid', ?string $recurringToken = null): ChipApi
{
    $payload = [
        'id' => $purchaseId,
        'status' => $status,
        'transaction_data' => ['payment_method' => 'visa'],
    ];

    if ($recurringToken !== null) {
        $payload['recurring_token'] = $recurringToken;
    }

    $mockHandler = new MockHandler([
        new Response(200, [], json_encode($payload)),
    ]);

    return new ChipApi(
        brandId: 'BRAND123',
        apiKey: 'secret',
        config: ['handler' => HandlerStack::create($mockHandler)],
    );
}

it('finalizes a succeeded chip donation', function () {
    $organization = Organization::factory()->create([
        'chip_brand_id' => 'BRAND123',
        'chip_api_key' => 'secret',
    ]);
    $campaign = Campaign::factory()->for($organization)->create([
        'collected_amount' => 0,
    ]);
    $donation = Donation::factory()->for($campaign)->create([
        'status' => DonationStatus::Pending,
        'gross_amount' => 100.00,
        'base_amount' => 100.00,
        'chip_purchase_id' => 'PURCHASE_ONE',
    ]);

    ChipApiFactory::fake(make: fn () => fakeChipPurchaseResponse('PURCHASE_ONE'));

    $response = $this->postJson(route('chip.finalize', $donation));

    $response->assertOk()->assertJson(['finalized' => true]);

    $donation->refresh();
    $campaign->refresh();

    expect($donation->status)->toBe(DonationStatus::Succeeded)
        ->and($donation->finalized_at)->not->toBeNull()
        ->and((float) $campaign->collected_amount)->toBe(100.00)
        ->and($donation->processingFee)->not->toBeNull();
});

it('creates a subscription when finalizing a recurring chip donation', function () {
    $organization = Organization::factory()->create([
        'chip_brand_id' => 'BRAND123',
        'chip_api_key' => 'secret',
    ]);
    $campaign = Campaign::factory()->for($organization)->create([
        'collected_amount' => 0,
    ]);
    $donation = Donation::factory()->for($campaign)->create([
        'status' => DonationStatus::Pending,
        'gross_amount' => 50.00,
        'base_amount' => 50.00,
        'type' => DonationType::Recurring,
        'chip_purchase_id' => 'PURCHASE_RECURRING',
    ]);

    ChipApiFactory::fake(make: fn () => fakeChipPurchaseResponse('PURCHASE_RECURRING', 'paid', 'RECUR_TOKEN_123'));

    $this->postJson(route('chip.finalize', $donation))->assertOk();

    $donation->refresh();

    expect($donation->subscription_id)->not->toBeNull()
        ->and($donation->chip_recurring_token)->toBe('RECUR_TOKEN_123');

    $subscription = Subscription::query()->whereKey($donation->subscription_id)->first();

    expect($subscription)->not->toBeNull()
        ->and($subscription->chip_recurring_token)->toBe('RECUR_TOKEN_123')
        ->and($subscription->campaign_id)->toBe($campaign->id);
});

it('is idempotent when finalizing multiple times', function () {
    $organization = Organization::factory()->create([
        'chip_brand_id' => 'BRAND123',
        'chip_api_key' => 'secret',
    ]);
    $campaign = Campaign::factory()->for($organization)->create([
        'collected_amount' => 0,
    ]);
    $donation = Donation::factory()->for($campaign)->create([
        'status' => DonationStatus::Pending,
        'gross_amount' => 75.00,
        'base_amount' => 75.00,
        'chip_purchase_id' => 'PURCHASE_DUPE',
    ]);

    ChipApiFactory::fake(make: fn () => fakeChipPurchaseResponse('PURCHASE_DUPE'));

    $this->postJson(route('chip.finalize', $donation))->assertOk();
    $this->postJson(route('chip.finalize', $donation))->assertOk();

    $campaign->refresh();

    expect((float) $campaign->collected_amount)->toBe(75.00);
});

it('returns a 4xx error when the chip purchase is not completed', function () {
    $organization = Organization::factory()->create([
        'chip_brand_id' => 'BRAND123',
        'chip_api_key' => 'secret',
    ]);
    $campaign = Campaign::factory()->for($organization)->create();
    $donation = Donation::factory()->for($campaign)->create([
        'status' => DonationStatus::Pending,
        'chip_purchase_id' => 'PURCHASE_PENDING',
    ]);

    ChipApiFactory::fake(make: fn () => fakeChipPurchaseResponse('PURCHASE_PENDING', 'pending'));

    $this->postJson(route('chip.finalize', $donation))
        ->assertStatus(422)
        ->assertJson(['finalized' => false]);

    $donation->refresh();

    expect($donation->finalized_at)->toBeNull();
});

it('finalizes a donation from a chip webhook', function () {
    $organization = Organization::factory()->create([
        'chip_brand_id' => 'BRAND123',
        'chip_api_key' => 'secret',
    ]);
    $campaign = Campaign::factory()->for($organization)->create([
        'collected_amount' => 0,
    ]);
    $donation = Donation::factory()->for($campaign)->create([
        'status' => DonationStatus::Pending,
        'gross_amount' => 40.00,
        'base_amount' => 40.00,
        'chip_purchase_id' => 'PURCHASE_EVENT',
    ]);

    ChipApiFactory::fake(make: fn () => fakeChipPurchaseResponse('PURCHASE_EVENT'));

    $job = new ProcessChipWebhook(['id' => 'PURCHASE_EVENT', 'event_type' => 'purchase.paid']);
    $job->handle(app(FinalizeDonation::class));

    $donation->refresh();
    $campaign->refresh();

    expect($donation->status)->toBe(DonationStatus::Succeeded)
        ->and($donation->finalized_at)->not->toBeNull()
        ->and((float) $campaign->collected_amount)->toBe(40.00);
});

it('does not queue the receipt again when finalizing an already finalized donation', function () {
    Queue::fake();

    $organization = Organization::factory()->create([
        'chip_brand_id' => 'BRAND123',
        'chip_api_key' => 'secret',
    ]);
    $campaign = Campaign::factory()->for($organization)->create(['collected_amount' => 0]);
    $donation = Donation::factory()->for($campaign)->create([
        'status' => DonationStatus::Pending,
        'gross_amount' => 40.00,
        'base_amount' => 40.00,
        'chip_purchase_id' => 'PURCHASE_SIDE_EFFECTS',
    ]);

    ChipApiFactory::fake(make: fn () => fakeChipPurchaseResponse('PURCHASE_SIDE_EFFECTS'));
    app(FinalizeDonation::class)->finalize($donation);

    ChipApiFactory::fake(make: fn () => fakeChipPurchaseResponse('PURCHASE_SIDE_EFFECTS'));
    app(FinalizeDonation::class)->finalize($donation->fresh());

    Queue::assertPushed(SendDonationReceipt::class, 1);
    Queue::assertPushed(SendNewDonationNotification::class, 1);
    Queue::assertPushed(SendMetaConversionEvent::class, 1);
});
