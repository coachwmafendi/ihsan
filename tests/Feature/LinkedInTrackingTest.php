<?php

use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Enums\ElementType;
use App\Enums\TrackingProvider;
use App\Jobs\SendLinkedInConversionEvent;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Element;
use App\Models\Organization;
use App\Models\TrackingConfiguration;
use App\Models\TrackingEvent;
use Illuminate\Support\Facades\Http;

uses()->group('tracking');

it('injects linkedin insight tag when configured', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'type' => ElementType::Form,
    ]);

    TrackingConfiguration::factory()->for($organization)->linkedin([
        'partner_id' => '1234567',
    ])->create();

    $response = $this->get(route('donations.show', $element));

    $response->assertOk();
    $response->assertSee('https://snap.licdn.com/li.lms-analytics/insight.min.js', false);
    $response->assertSee("_linkedin_partner_id = '1234567'", false);
    $response->assertSee('px.ads.linkedin.com/collect/?pid=1234567', false);
});

it('does not inject linkedin tag when unconfigured', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'type' => ElementType::Form,
    ]);

    $response = $this->get(route('donations.show', $element));

    $response->assertOk();
    $response->assertDontSee('snap.licdn.com', false);
});

it('maps linkedin events in ihsantrack', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'type' => ElementType::Form,
    ]);

    TrackingConfiguration::factory()->for($organization)->linkedin([
        'partner_id' => '1234567',
        'conversion_id' => '87654321',
    ])->create();

    $response = $this->get(route('donations.show', $element));

    $response->assertOk();
    $response->assertSee('var linkedInConversionId = tracking.linkedin.conversion_id;', false);
    $response->assertSee("lintrk('track', { conversion_id: linkedInConversionId })", false);
    $response->assertSee('conversion_value: payload ? payload.value : undefined', false);
});

it('sends linkedin purchase conversion via api', function () {
    Http::fake([
        'https://api.linkedin.com/*' => Http::response(['status' => 'ACCEPTED'], 202),
    ]);

    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donation = Donation::factory()->for($campaign)->create([
        'public_id' => 'D1234567',
        'gross_amount' => 100.00,
        'currency' => 'myr',
        'status' => DonationStatus::Succeeded,
        'type' => DonationType::OneTime,
    ]);

    TrackingConfiguration::factory()->for($organization)->linkedin([
        'conversion_id' => '87654321',
        'access_token' => 'test-token',
    ])->create();

    (new SendLinkedInConversionEvent($donation))->handle();

    Http::assertSent(function ($request) {
        return str_starts_with($request->url(), 'https://api.linkedin.com/rest/conversions');
    });

    $event = TrackingEvent::query()->firstOrFail();

    expect($event->status)->toBe('sent')
        ->and($event->provider)->toBe(TrackingProvider::LinkedIn)
        ->and($event->event_name)->toBe('Purchase')
        ->and((float) $event->amount)->toBe(100.0)
        ->and($event->currency)->toBe('MYR');
});

it('does not send linkedin conversion without access token', function () {
    Http::fake();

    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donation = Donation::factory()->for($campaign)->create([
        'gross_amount' => 50.00,
        'currency' => 'myr',
        'status' => DonationStatus::Succeeded,
    ]);

    TrackingConfiguration::factory()->for($organization)->linkedin([
        'conversion_id' => '87654321',
        'access_token' => '',
    ])->create();

    (new SendLinkedInConversionEvent($donation))->handle();

    Http::assertNotSent(fn ($request) => str_contains($request->url(), 'linkedin.com'));
    expect(TrackingEvent::query()->count())->toBe(0);
});

it('does not send linkedin conversion without conversion id', function () {
    Http::fake();

    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donation = Donation::factory()->for($campaign)->create([
        'gross_amount' => 50.00,
        'currency' => 'myr',
        'status' => DonationStatus::Succeeded,
    ]);

    TrackingConfiguration::factory()->for($organization)->linkedin([
        'conversion_id' => '',
        'access_token' => 'test-token',
    ])->create();

    (new SendLinkedInConversionEvent($donation))->handle();

    Http::assertNotSent(fn ($request) => str_contains($request->url(), 'linkedin.com'));
    expect(TrackingEvent::query()->count())->toBe(0);
});

it('records failed linkedin conversion when api errors', function () {
    Http::fake([
        'https://api.linkedin.com/*' => Http::response(['message' => 'Invalid token'], 401),
    ]);

    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donation = Donation::factory()->for($campaign)->create([
        'gross_amount' => 50.00,
        'currency' => 'myr',
        'status' => DonationStatus::Succeeded,
    ]);

    TrackingConfiguration::factory()->for($organization)->linkedin([
        'conversion_id' => '87654321',
        'access_token' => 'test-token',
    ])->create();

    (new SendLinkedInConversionEvent($donation))->handle();

    expect(TrackingEvent::query()->firstOrFail()->status)->toBe('failed');
});
