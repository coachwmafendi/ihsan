<?php

use App\Enums\ElementType;
use App\Enums\TrackingProvider;
use App\Jobs\SendGa4TrackingEvent;
use App\Jobs\SendMetaTrackingEvent;
use App\Livewire\DonationForm;
use App\Models\Campaign;
use App\Models\Element;
use App\Models\Organization;
use App\Models\TrackingConfiguration;
use App\Models\TrackingEvent;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

uses()->group('tracking');

beforeEach(function () {
    Http::fake([
        'graph.facebook.com/*' => Http::response(['events_received' => 1], 200),
        'www.google-analytics.com/*' => Http::response('', 204),
    ]);
});

it('dispatches server-side pageview events from donation form when meta and ga4 are enabled', function () {
    Queue::fake();

    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'type' => ElementType::Form,
    ]);

    TrackingConfiguration::factory()->for($organization)->meta([
        'pixel_id' => '123456789012345',
        'access_token' => 'token',
    ])->create();

    TrackingConfiguration::factory()->for($organization)->ga4([
        'measurement_id' => 'G-TEST123456',
        'api_secret' => 'secret',
    ])->create();

    Livewire::test(DonationForm::class, ['element' => $element])
        ->call('trackServerPageView');

    Queue::assertPushed(SendMetaTrackingEvent::class, fn ($job) => $job->eventName === 'PageView');
    Queue::assertPushed(SendGa4TrackingEvent::class, fn ($job) => $job->eventName === 'page_view');
});

it('dispatches server-side initiate checkout events from donation form', function () {
    Queue::fake();

    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'type' => ElementType::Form,
    ]);

    TrackingConfiguration::factory()->for($organization)->meta([
        'pixel_id' => '123456789012345',
        'access_token' => 'token',
    ])->create();

    TrackingConfiguration::factory()->for($organization)->ga4([
        'measurement_id' => 'G-TEST123456',
        'api_secret' => 'secret',
    ])->create();

    Livewire::test(DonationForm::class, ['element' => $element])
        ->set('amount', 50)
        ->set('currency', 'myr')
        ->call('trackServerInitiateCheckout');

    Queue::assertPushed(SendMetaTrackingEvent::class, fn ($job) => $job->eventName === 'InitiateCheckout');
    Queue::assertPushed(SendGa4TrackingEvent::class, fn ($job) => $job->eventName === 'begin_checkout');
});

it('records a meta pageview tracking event', function () {
    $organization = Organization::factory()->create();
    TrackingConfiguration::factory()->for($organization)->meta([
        'pixel_id' => '123456789012345',
        'access_token' => 'token',
    ])->create();

    (new SendMetaTrackingEvent(
        $organization->id,
        'PageView',
        'https://ihsan.test/donate/test',
        ['client_ip_address' => '127.0.0.1', 'client_user_agent' => 'test'],
        [],
        'Test Campaign',
    ))->handle();

    $event = TrackingEvent::query()
        ->where('organization_id', $organization->id)
        ->where('provider', TrackingProvider::Meta)
        ->where('event_name', 'PageView')
        ->firstOrFail();

    expect($event->status)->toBe('sent');
});

it('records a ga4 pageview tracking event', function () {
    $organization = Organization::factory()->create();
    TrackingConfiguration::factory()->for($organization)->ga4([
        'measurement_id' => 'G-TEST123456',
        'api_secret' => 'secret',
    ])->create();

    (new SendGa4TrackingEvent(
        $organization->id,
        'page_view',
        'client-id-123',
        'https://ihsan.test/donate/test',
        ['page_location' => 'https://ihsan.test/donate/test'],
        'Test Campaign',
    ))->handle();

    $event = TrackingEvent::query()
        ->where('organization_id', $organization->id)
        ->where('provider', TrackingProvider::GoogleAnalytics4)
        ->where('event_name', 'page_view')
        ->firstOrFail();

    expect($event->status)->toBe('sent');
});
