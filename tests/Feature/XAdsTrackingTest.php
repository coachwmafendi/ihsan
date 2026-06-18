<?php

use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Enums\ElementType;
use App\Enums\TrackingProvider;
use App\Jobs\SendXAdsConversionEvent;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Element;
use App\Models\Organization;
use App\Models\TrackingConfiguration;
use App\Models\TrackingEvent;
use Illuminate\Support\Facades\Http;

uses()->group('tracking');

it('injects x ads pixel when configured', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'type' => ElementType::Form,
    ]);

    TrackingConfiguration::factory()->for($organization)->xAds([
        'pixel_id' => 'o1234',
    ])->create();

    $response = $this->get(route('donations.show', $element));

    $response->assertOk();
    $response->assertSee('https://static.ads-twitter.com/uwt.js', false);
    $response->assertSee("twq('config', 'o1234')", false);
});

it('does not inject x ads pixel when unconfigured', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'type' => ElementType::Form,
    ]);

    $response = $this->get(route('donations.show', $element));

    $response->assertOk();
    $response->assertDontSee('static.ads-twitter.com', false);
});

it('maps x ads events in ihsantrack', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'type' => ElementType::Form,
    ]);

    TrackingConfiguration::factory()->for($organization)->xAds([
        'pixel_id' => 'o1234',
        'conversion_id' => 'tw-o1234-abcde',
    ])->create();

    $response = $this->get(route('donations.show', $element));

    $response->assertOk();
    $response->assertSee("twq('event', xAdsConversionId)", false);
    $response->assertSee("twq('event', xAdsConversionId, {", false);
});

it('sends x ads purchase conversion via api', function () {
    Http::fake([
        'https://ads-api.twitter.com/*' => Http::response(['data' => ['message' => 'success']], 200),
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

    TrackingConfiguration::factory()->for($organization)->xAds([
        'conversion_id' => 'tw-o1234-abcde',
        'access_token' => 'test-token',
    ])->create();

    (new SendXAdsConversionEvent($donation))->handle();

    Http::assertSent(function ($request) {
        return str_starts_with($request->url(), 'https://ads-api.twitter.com/12/measurement/conversions/events');
    });

    $event = TrackingEvent::query()->firstOrFail();

    expect($event->status)->toBe('sent')
        ->and($event->provider)->toBe(TrackingProvider::XAds)
        ->and($event->event_name)->toBe('Purchase')
        ->and((float) $event->amount)->toBe(100.0)
        ->and($event->currency)->toBe('MYR')
        ->and($event->payload['user_data']['em'])->toBe(hash('sha256', 'donor@example.com'));
});

it('does not send x ads conversion without access token', function () {
    Http::fake();

    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donation = Donation::factory()->for($campaign)->create([
        'gross_amount' => 50.00,
        'currency' => 'myr',
        'status' => DonationStatus::Succeeded,
    ]);

    TrackingConfiguration::factory()->for($organization)->xAds([
        'conversion_id' => 'tw-o1234-abcde',
        'access_token' => '',
    ])->create();

    (new SendXAdsConversionEvent($donation))->handle();

    Http::assertNotSent(fn ($request) => str_contains($request->url(), 'twitter.com'));
    expect(TrackingEvent::query()->count())->toBe(0);
});

it('does not send x ads conversion without conversion id', function () {
    Http::fake();

    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donation = Donation::factory()->for($campaign)->create([
        'gross_amount' => 50.00,
        'currency' => 'myr',
        'status' => DonationStatus::Succeeded,
    ]);

    TrackingConfiguration::factory()->for($organization)->xAds([
        'conversion_id' => '',
        'access_token' => 'test-token',
    ])->create();

    (new SendXAdsConversionEvent($donation))->handle();

    Http::assertNotSent(fn ($request) => str_contains($request->url(), 'twitter.com'));
    expect(TrackingEvent::query()->count())->toBe(0);
});

it('does not send x ads conversion when track_conversions is disabled', function () {
    Http::fake();

    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donation = Donation::factory()->for($campaign)->create([
        'gross_amount' => 50.00,
        'currency' => 'myr',
        'status' => DonationStatus::Succeeded,
    ]);

    TrackingConfiguration::factory()->for($organization)->xAds(
        credentials: ['conversion_id' => 'tw-o1234-abcde', 'access_token' => 'test-token'],
        options: ['track_conversions' => false],
    )->create();

    (new SendXAdsConversionEvent($donation))->handle();

    Http::assertNotSent(fn ($request) => str_contains($request->url(), 'twitter.com'));
    expect(TrackingEvent::query()->count())->toBe(0);
});

it('records failed x ads conversion when api errors', function () {
    Http::fake([
        'https://ads-api.twitter.com/*' => Http::response(['error' => ['message' => 'Invalid']], 400),
    ]);

    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donation = Donation::factory()->for($campaign)->create([
        'gross_amount' => 50.00,
        'currency' => 'myr',
        'status' => DonationStatus::Succeeded,
    ]);

    TrackingConfiguration::factory()->for($organization)->xAds([
        'conversion_id' => 'tw-o1234-abcde',
        'access_token' => 'test-token',
    ])->create();

    (new SendXAdsConversionEvent($donation))->handle();

    expect(TrackingEvent::query()->firstOrFail()->status)->toBe('failed');
});
