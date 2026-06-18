<?php

use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Enums\ElementType;
use App\Enums\TrackingProvider;
use App\Jobs\SendSnapchatConversionEvent;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Element;
use App\Models\Organization;
use App\Models\TrackingConfiguration;
use App\Models\TrackingEvent;
use Illuminate\Support\Facades\Http;

uses()->group('tracking');

it('injects snapchat pixel when configured', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'type' => ElementType::Form,
    ]);

    TrackingConfiguration::factory()->for($organization)->snapchat([
        'pixel_id' => 'pixel-snap-123',
    ])->create();

    $response = $this->get(route('donations.show', $element));

    $response->assertOk();
    $response->assertSee('https://sc-static.net/scevent.min.js', false);
    $response->assertSee("snaptr('init', 'pixel-snap-123'", false);
    $response->assertSee("snaptr('track', 'PAGE_VIEW')", false);
});

it('does not inject snapchat pixel when unconfigured', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'type' => ElementType::Form,
    ]);

    $response = $this->get(route('donations.show', $element));

    $response->assertOk();
    $response->assertDontSee('sc-static.net', false);
});

it('maps snapchat events in ihsantrack', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'type' => ElementType::Form,
    ]);

    TrackingConfiguration::factory()->for($organization)->snapchat([
        'pixel_id' => 'pixel-snap-123',
    ])->create();

    $response = $this->get(route('donations.show', $element));

    $response->assertOk();
    $response->assertSee("snaptr('track', 'START_CHECKOUT', snapArgs)", false);
    $response->assertSee("snaptr('track', 'PURCHASE', snapArgs)", false);
});

it('sends snapchat purchase conversion via api', function () {
    Http::fake([
        'https://tr.snapchat.com/*' => Http::response(['status' => 'ok'], 200),
    ]);

    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create(['email' => 'donor@example.com']);
    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'public_id' => 'D1234567',
        'gross_amount' => 100.00,
        'currency' => 'myr',
        'status' => DonationStatus::Succeeded,
        'type' => DonationType::OneTime,
    ]);

    TrackingConfiguration::factory()->for($organization)->snapchat([
        'pixel_id' => 'pixel-snap-123',
        'access_token' => 'test-token',
    ])->create();

    (new SendSnapchatConversionEvent($donation))->handle();

    Http::assertSent(function ($request) {
        return str_starts_with($request->url(), 'https://tr.snapchat.com/v2/conversion');
    });

    $event = TrackingEvent::query()->firstOrFail();

    expect($event->status)->toBe('sent')
        ->and($event->provider)->toBe(TrackingProvider::Snapchat)
        ->and($event->event_name)->toBe('Purchase')
        ->and((float) $event->amount)->toBe(100.0)
        ->and($event->currency)->toBe('MYR');
});

it('does not send snapchat conversion without access token', function () {
    Http::fake();

    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donation = Donation::factory()->for($campaign)->create([
        'gross_amount' => 50.00,
        'currency' => 'myr',
        'status' => DonationStatus::Succeeded,
    ]);

    TrackingConfiguration::factory()->for($organization)->snapchat([
        'pixel_id' => 'pixel-snap-123',
        'access_token' => '',
    ])->create();

    (new SendSnapchatConversionEvent($donation))->handle();

    Http::assertNotSent(fn ($request) => str_contains($request->url(), 'snapchat.com'));
    expect(TrackingEvent::query()->count())->toBe(0);
});

it('does not send snapchat conversion when track_conversions is disabled', function () {
    Http::fake();

    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donation = Donation::factory()->for($campaign)->create([
        'gross_amount' => 50.00,
        'currency' => 'myr',
        'status' => DonationStatus::Succeeded,
    ]);

    TrackingConfiguration::factory()->for($organization)->snapchat(
        credentials: ['pixel_id' => 'pixel-snap-123', 'access_token' => 'test-token'],
        options: ['track_conversions' => false],
    )->create();

    (new SendSnapchatConversionEvent($donation))->handle();

    Http::assertNotSent(fn ($request) => str_contains($request->url(), 'snapchat.com'));
    expect(TrackingEvent::query()->count())->toBe(0);
});

it('records failed snapchat conversion when api errors', function () {
    Http::fake([
        'https://tr.snapchat.com/*' => Http::response(['error' => 'Invalid token'], 401),
    ]);

    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donation = Donation::factory()->for($campaign)->create([
        'gross_amount' => 50.00,
        'currency' => 'myr',
        'status' => DonationStatus::Succeeded,
    ]);

    TrackingConfiguration::factory()->for($organization)->snapchat([
        'pixel_id' => 'pixel-snap-123',
        'access_token' => 'test-token',
    ])->create();

    (new SendSnapchatConversionEvent($donation))->handle();

    expect(TrackingEvent::query()->firstOrFail()->status)->toBe('failed');
});
