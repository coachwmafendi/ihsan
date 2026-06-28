<?php

use App\Actions\Chip\ChipApiFactory;
use App\Actions\Chip\FinalizeDonation;
use App\Enums\DonationStatus;
use App\Jobs\ProcessChipWebhook;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\WebhookLog;
use Chip\ChipApi;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Queue;

uses()->group('chip');

beforeEach(function () {
    ChipApiFactory::resetFake();
});

afterEach(function () {
    ChipApiFactory::resetFake();
});

it('rejects chip webhook with missing signature', function () {
    $response = $this->postJson(route('chip.webhook'), [
        'id' => 'PURCHASE123',
    ]);

    $response->assertStatus(400)
        ->assertJsonPath('error', 'Missing CHIP webhook signature.');
});

it('rejects chip webhook for unrecognized purchase', function () {
    $response = $this->postJson(route('chip.webhook'), [
        'id' => 'UNKNOWN_PURCHASE',
    ], [
        'X-Signature' => 'valid-signature',
    ]);

    $response->assertStatus(400)
        ->assertJsonPath('error', 'CHIP purchase not recognized.');
});

it('accepts a valid chip webhook and dispatches the processor job', function () {
    Queue::fake([ProcessChipWebhook::class]);

    $organization = Organization::factory()->create([
        'chip_brand_id' => 'BRAND123',
        'chip_api_key' => 'secret',
    ]);
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'chip_purchase_id' => 'PURCHASE123',
        'status' => DonationStatus::Pending,
    ]);

    ChipApiFactory::fake(publicKey: 'chip-public-key', verifyWebhook: true);

    $payload = [
        'id' => 'PURCHASE123',
        'event_type' => 'purchase.paid',
    ];

    $response = $this->postJson(route('chip.webhook'), $payload, [
        'X-Signature' => 'valid-signature',
    ]);

    $response->assertOk()
        ->assertJson(['received' => true]);

    expect(WebhookLog::where('gateway', 'chip')->where('stripe_event_id', 'PURCHASE123')->exists())->toBeTrue();

    Queue::assertPushed(ProcessChipWebhook::class, fn (ProcessChipWebhook $job): bool => $job->payload === $payload);
});

it('deduplicates chip webhook events', function () {
    Queue::fake([ProcessChipWebhook::class]);

    $organization = Organization::factory()->create([
        'chip_brand_id' => 'BRAND123',
        'chip_api_key' => 'secret',
    ]);
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    Donation::factory()->for($campaign)->for($donor)->create([
        'chip_purchase_id' => 'PURCHASE123',
        'status' => DonationStatus::Pending,
    ]);

    ChipApiFactory::fake(publicKey: 'chip-public-key', verifyWebhook: true);

    $payload = [
        'id' => 'PURCHASE123',
        'event_type' => 'purchase.paid',
    ];

    $this->postJson(route('chip.webhook'), $payload, ['X-Signature' => 'valid-signature'])->assertOk();
    $this->postJson(route('chip.webhook'), $payload, ['X-Signature' => 'valid-signature'])->assertOk();

    expect(WebhookLog::where('gateway', 'chip')->where('stripe_event_id', 'PURCHASE123')->count())->toBe(1);
});

it('processes chip webhook and syncs donation', function () {
    $organization = Organization::factory()->create([
        'chip_brand_id' => 'BRAND123',
        'chip_api_key' => 'secret',
        'processing_fee_override' => 2.5,
    ]);
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'chip_purchase_id' => 'PURCHASE123',
        'gross_amount' => 100.00,
        'status' => DonationStatus::Pending,
    ]);

    $mockHandler = new MockHandler([
        new Response(200, [], json_encode([
            'id' => 'PURCHASE123',
            'status' => 'paid',
            'transaction_data' => [
                'payment_method' => 'fpx',
            ],
        ])),
    ]);

    ChipApiFactory::fake(make: function (Organization $organization) use ($mockHandler): ChipApi {
        return new ChipApi(
            $organization->chip_brand_id,
            $organization->chip_api_key,
            config: ['handler' => HandlerStack::create($mockHandler)],
        );
    });

    (new ProcessChipWebhook([
        'id' => 'PURCHASE123',
        'event_type' => 'purchase.paid',
    ]))->handle(app(FinalizeDonation::class));

    $donation->refresh();

    expect($donation->status)->toBe(DonationStatus::Succeeded)
        ->and($donation->payment_method_brand)->toBe('fpx');
});
