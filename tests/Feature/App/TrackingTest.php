<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Livewire\App\Settings\Tracking;
use App\Models\Organization;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->user = User::factory()->for($this->organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);
});

it('toggles advanced and attribution sections on and off', function () {
    $this->actingAs($this->user);

    Livewire::test(Tracking::class)
        ->assertSet('showAdvanced', false)
        ->assertSet('showAttribution', false)
        ->call('toggleShowAdvanced')
        ->assertSet('showAdvanced', true)
        ->call('toggleShowAttribution')
        ->assertSet('showAttribution', true)
        ->call('toggleShowAdvanced')
        ->assertSet('showAdvanced', false)
        ->call('toggleShowAttribution')
        ->assertSet('showAttribution', false);
});

it('tests the meta connection with a real server event', function () {
    Http::fake([
        'graph.facebook.com/v18.0/*/events' => Http::response(['events_received' => 1], 200),
    ]);

    $this->actingAs($this->user);

    Livewire::test(Tracking::class)
        ->set('credentials.meta.pixel_id', '123456789012345')
        ->set('credentials.meta.access_token', 'test-token')
        ->call('testConnection', 'meta')
        ->assertDispatched('notify', type: 'success');

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'graph.facebook.com/v18.0/123456789012345/events')
            && $request->data()['data'][0]['event_name'] === 'PageView';
    });
});

it('shows an error when testing meta without an access token', function () {
    $this->actingAs($this->user);

    Livewire::test(Tracking::class)
        ->set('credentials.meta.pixel_id', '123456789012345')
        ->set('credentials.meta.access_token', '')
        ->call('testConnection', 'meta')
        ->assertDispatched('notify', type: 'error');
});

it('shows an error when meta test connection fails', function () {
    Http::fake([
        'graph.facebook.com/v18.0/*/events' => Http::response(['error' => ['message' => 'Invalid token']], 400),
    ]);

    $this->actingAs($this->user);

    Livewire::test(Tracking::class)
        ->set('credentials.meta.pixel_id', '123456789012345')
        ->set('credentials.meta.access_token', 'bad-token')
        ->call('testConnection', 'meta')
        ->assertDispatched('notify', type: 'error');
});

it('tests the google analytics 4 measurement id is reachable', function () {
    Http::fake([
        'www.googletagmanager.com/gtag/js?id=G-18P2G9HYKC' => Http::response("gtag('config', 'G-18P2G9HYKC');", 200),
    ]);

    $this->actingAs($this->user);

    Livewire::test(Tracking::class)
        ->set('credentials.ga4.measurement_id', 'G-18P2G9HYKC')
        ->call('testConnection', 'ga4')
        ->assertDispatched('notify', type: 'success');

    Http::assertSent(function ($request) {
        return $request->url() === 'https://www.googletagmanager.com/gtag/js?id=G-18P2G9HYKC';
    });
});

it('shows an error when google analytics 4 test connection fails', function () {
    Http::fake([
        'www.googletagmanager.com/gtag/js?id=G-BADID' => Http::response('Not Found', 404),
    ]);

    $this->actingAs($this->user);

    Livewire::test(Tracking::class)
        ->set('credentials.ga4.measurement_id', 'G-BADID')
        ->call('testConnection', 'ga4')
        ->assertDispatched('notify', type: 'error');
});
