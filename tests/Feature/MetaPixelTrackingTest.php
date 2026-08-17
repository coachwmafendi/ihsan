<?php

use App\Actions\Stripe\CreatePaymentIntent;
use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Enums\ElementType;
use App\Enums\TrackingProvider;
use App\Jobs\SendMetaConversionEvent;
use App\Livewire\DonationForm;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Element;
use App\Models\Organization;
use App\Models\TrackingConfiguration;
use App\Models\TrackingEvent;
use App\Support\ClientInfo;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Stripe\PaymentIntent;

uses()->group('tracking');

it('injects meta pixel base code when meta is configured', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'type' => ElementType::Form,
    ]);

    TrackingConfiguration::factory()->for($organization)->meta([
        'pixel_id' => '123456789012345',
    ])->create();

    $response = $this->get(route('donations.show', $element));

    $response->assertOk();
    $response->assertSee('https://connect.facebook.net/en_US/fbevents.js', false);
    $response->assertSee("fbq('init', '123456789012345')", false);
    $response->assertSee("fbq('track', 'PageView', {}, { eventID: window.__IHSAN_PAGEVIEW_ID__ })", false);
    $response->assertSee('window.IhsanTrack = function', false);
    $response->assertSee('www.facebook.com/tr?id=123456789012345&ev=PageView&noscript=1', false);
});

it('does not inject meta pixel when tracking is not configured', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'type' => ElementType::Form,
    ]);

    $response = $this->get(route('donations.show', $element));

    $response->assertOk();
    $response->assertDontSee('https://connect.facebook.net/en_US/fbevents.js', false);
    $response->assertDontSee("fbq('init'", false);
    $response->assertSee('window.__IHSAN_TRACKING__ =', false);
    $response->assertSee('window.IhsanTrack = function', false);
});

it('does not inject meta pixel when meta is disabled', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'type' => ElementType::Form,
    ]);

    TrackingConfiguration::factory()->for($organization)->meta()->inactive()->create();

    $response = $this->get(route('donations.show', $element));

    $response->assertOk();
    $response->assertDontSee('https://connect.facebook.net/en_US/fbevents.js', false);
    $response->assertDontSee("fbq('init'", false);
});

it('respects the track page views option', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'type' => ElementType::Form,
    ]);

    TrackingConfiguration::factory()->for($organization)->meta(
        credentials: ['pixel_id' => '123456789012345'],
        options: [
            'track_page_views' => false,
            'track_donation_starts' => true,
            'track_successful_donations' => true,
        ],
    )->create();

    $response = $this->get(route('donations.show', $element));

    $response->assertOk();
    $response->assertSee("fbq('init', '123456789012345')", false);
    $response->assertDontSee("fbq('track', 'PageView')", false);
});

it('dispatches meta conversion event job after payment confirmation', function () {
    Queue::fake([SendMetaConversionEvent::class]);

    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'type' => ElementType::Form,
    ]);

    TrackingConfiguration::factory()->for($organization)->meta()->create();

    $donor = Donor::factory()->create();
    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'stripe_payment_intent_id' => 'pi_dispatch_test',
        'gross_amount' => 50.00,
        'currency' => 'myr',
        'status' => DonationStatus::Pending,
        'type' => DonationType::OneTime,
    ]);

    $paymentIntent = PaymentIntent::constructFrom([
        'id' => 'pi_dispatch_test',
        'object' => 'payment_intent',
        'payment_method' => null,
        'charges' => [
            'object' => 'list',
            'data' => [[
                'id' => 'ch_dispatch_test',
                'object' => 'charge',
                'balance_transaction' => null,
                'payment_method_details' => null,
            ]],
        ],
    ]);

    Livewire::test(DonationForm::class, ['element' => $element])
        ->call('confirmPayment', 'pi_dispatch_test', $paymentIntent);

    Queue::assertPushed(SendMetaConversionEvent::class, function (SendMetaConversionEvent $job) use ($donation) {
        return $job->donation->is($donation);
    });
});

it('sends purchase event via meta conversion api', function () {
    Http::fake([
        'https://graph.facebook.com/*' => Http::response(['events_received' => 1], 200),
    ]);

    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create([
        'email' => 'donor@example.com',
    ]);
    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'public_id' => 'D1234567',
        'gross_amount' => 100.00,
        'currency' => 'myr',
        'status' => DonationStatus::Succeeded,
        'type' => DonationType::OneTime,
        'page_url' => config('app.url').'/donate/test',
        'utm_params' => [
            'fbclid' => 'abc123',
            'utm_source' => 'facebook',
            'utm_campaign' => 'ramadan-2026',
        ],
    ]);

    TrackingConfiguration::factory()->for($organization)->meta([
        'pixel_id' => '123456789012345',
        'access_token' => 'test-access-token',
    ])->create();

    (new SendMetaConversionEvent($donation))->handle();

    Http::assertSent(function ($request) {
        return str_starts_with($request->url(), 'https://graph.facebook.com/'.config('services.meta.api_version').'/123456789012345/events');
    });

    $event = TrackingEvent::query()
        ->where('donation_id', $donation->id)
        ->where('event_name', 'Purchase')
        ->firstOrFail();

    expect($event->status)->toBe('sent')
        ->and((float) $event->amount)->toBe(100.0)
        ->and($event->currency)->toBe('MYR')
        ->and($event->provider)->toBe(TrackingProvider::Meta);

    $payload = $event->payload;
    expect($payload['event_name'])->toBe('Purchase')
        ->and($payload['event_id'])->toBe('purchase_D1234567')
        ->and($payload['action_source'])->toBe('website')
        ->and($payload['custom_data']['value'])->toEqual(100.0)
        ->and($payload['custom_data']['currency'])->toBe('MYR')
        ->and($payload['user_data']['em'])->toBe(hash('sha256', 'donor@example.com'))
        ->and($payload['event_source_url'])->toBe(config('app.url').'/donate/test');

    expect($payload['user_data']['fbc'] ?? '')->toContain('abc123');
});

it('does not send conversion event when meta purchase tracking is disabled', function () {
    Http::fake();

    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donation = Donation::factory()->for($campaign)->create([
        'gross_amount' => 50.00,
        'currency' => 'myr',
        'status' => DonationStatus::Succeeded,
    ]);

    TrackingConfiguration::factory()->for($organization)->meta(
        credentials: ['pixel_id' => '123456789012345', 'access_token' => 'token'],
        options: ['track_successful_donations' => false],
    )->create();

    (new SendMetaConversionEvent($donation))->handle();

    Http::assertNotSent(fn ($request) => str_contains($request->url(), 'facebook.com'));
    expect(TrackingEvent::query()->count())->toBe(0);
});

it('does not send conversion event when access token is missing', function () {
    Http::fake();

    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donation = Donation::factory()->for($campaign)->create([
        'gross_amount' => 50.00,
        'currency' => 'myr',
        'status' => DonationStatus::Succeeded,
    ]);

    TrackingConfiguration::factory()->for($organization)->meta(
        credentials: ['pixel_id' => '123456789012345', 'access_token' => ''],
    )->create();

    (new SendMetaConversionEvent($donation))->handle();

    Http::assertNotSent(fn ($request) => str_contains($request->url(), 'facebook.com'));
    expect(TrackingEvent::query()->count())->toBe(0);
});

it('records failed conversion event when api returns error', function () {
    Http::fake([
        'https://graph.facebook.com/*' => Http::response(['error' => ['message' => 'Invalid token']], 400),
    ]);

    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donation = Donation::factory()->for($campaign)->create([
        'gross_amount' => 50.00,
        'currency' => 'myr',
        'status' => DonationStatus::Succeeded,
    ]);

    TrackingConfiguration::factory()->for($organization)->meta([
        'pixel_id' => '123456789012345',
        'access_token' => 'test-token',
    ])->create();

    (new SendMetaConversionEvent($donation))->handle();

    $event = TrackingEvent::query()->firstOrFail();

    expect($event->status)->toBe('failed');
});

it('includes track purchase logic in the donation form javascript', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'type' => ElementType::Form,
    ]);

    $response = $this->get(route('donations.show', $element));

    $response->assertOk();
    $response->assertSee('trackPurchase()', false);
    $response->assertSee("eventID: 'purchase_' + this.donationPublicId", false);
    $response->assertSee("window.IhsanTrack('Purchase'", false);
    $response->assertSee('this.donationPublicId = this.$wire.donationPublicId', false);
});

it('uses the same event id format in browser and server purchase events', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'public_id' => 'D1234567',
        'gross_amount' => 100.00,
        'currency' => 'myr',
        'status' => DonationStatus::Succeeded,
    ]);

    TrackingConfiguration::factory()->for($organization)->meta([
        'pixel_id' => '123456789012345',
        'access_token' => 'test-token',
    ])->create();

    (new SendMetaConversionEvent($donation))->handle();

    $event = TrackingEvent::query()->firstOrFail();

    expect($event->payload['event_id'])->toBe('purchase_D1234567');
});

it('injects ga4 gtag script when google analytics 4 is configured', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'type' => ElementType::Form,
    ]);

    TrackingConfiguration::factory()->for($organization)->ga4([
        'measurement_id' => 'G-TEST123456',
    ])->create();

    $response = $this->get(route('donations.show', $element));

    $response->assertOk();
    $response->assertSee('https://www.googletagmanager.com/gtag/js?id=G-TEST123456', false);
    $response->assertSee("gtag('config', 'G-TEST123456')", false);
    $response->assertSee('window.IhsanTrack = function', false);
});

it('injects google ads conversion script when configured', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'type' => ElementType::Form,
    ]);

    TrackingConfiguration::factory()->for($organization)->googleAds([
        'conversion_id' => 'AW-123456789',
        'conversion_label' => 'TEST_LABEL_123',
    ])->create();

    $response = $this->get(route('donations.show', $element));

    $response->assertOk();
    $response->assertSee('https://www.googletagmanager.com/gtag/js?id=AW-123456789', false);
    $response->assertSee("gtag('config', 'AW-123456789')", false);
    $response->assertSee('AW-123456789/TEST_LABEL_123', false);
});

it('shares a single gtag script when both ga4 and google ads are configured', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'type' => ElementType::Form,
    ]);

    TrackingConfiguration::factory()->for($organization)->ga4(['measurement_id' => 'G-TEST123456'])->create();
    TrackingConfiguration::factory()->for($organization)->googleAds([
        'conversion_id' => 'AW-123456789',
        'conversion_label' => 'TEST_LABEL_123',
    ])->create();

    $html = $this->get(route('donations.show', $element))->assertOk()->getContent();

    expect(substr_count($html, 'googletagmanager.com/gtag/js'))->toBe(1)
        ->and($html)->toContain("gtag('config', 'G-TEST123456')")
        ->and($html)->toContain("gtag('config', 'AW-123456789')");
});

it('injects tiktok pixel script when configured', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'type' => ElementType::Form,
    ]);

    TrackingConfiguration::factory()->for($organization)->tiktok([
        'pixel_id' => 'CTESTPIXELID12345',
    ])->create();

    $response = $this->get(route('donations.show', $element));

    $response->assertOk();
    $response->assertSee('analytics.tiktok.com/i18n/pixel/sdk.js', false);
    $response->assertSee('CTESTPIXELID12345', false);
    $response->assertSee('ttq.page()', false);
    $response->assertSee('window.IhsanTrack = function', false);
});

it('maps events to provider-specific names in IhsanTrack', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'type' => ElementType::Form,
    ]);

    TrackingConfiguration::factory()->for($organization)->meta()->create();
    TrackingConfiguration::factory()->for($organization)->ga4()->create();
    TrackingConfiguration::factory()->for($organization)->googleAds()->create();
    TrackingConfiguration::factory()->for($organization)->tiktok()->create();

    $response = $this->get(route('donations.show', $element));

    $response->assertOk();
    $response->assertSee("gtag('event', 'begin_checkout'", false);
    $response->assertSee("gtag('event', 'purchase'", false);
    $response->assertSee("gtag('event', 'conversion'", false);
    $response->assertSee("ttq.track('InitiateCheckout'", false);
    $response->assertSee("ttq.track('CompletePayment'", false);
});

it('captures attribution parameters in utm_params on donation creation', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create([
        'suggested_amounts' => [50, 100, 200],
    ]);
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'type' => ElementType::Form,
        'config' => ['default_amount' => 50, 'allow_monthly' => false],
    ]);

    $paymentIntent = PaymentIntent::constructFrom([
        'id' => 'pi_attr_test',
        'client_secret' => 'pi_attr_test_secret',
    ]);

    $this->mock(CreatePaymentIntent::class, function ($mock) use ($paymentIntent): void {
        $mock->shouldReceive('create')->once()->andReturn($paymentIntent);
    });

    Livewire::withQueryParams([
        'utm_source' => 'facebook',
        'utm_medium' => 'cpc',
        'utm_campaign' => 'ramadan-2026',
        'fbclid' => 'abc123',
        'gclid' => 'xyz789',
    ])->test(DonationForm::class, ['element' => $element])
        ->set('frequency', 'one_time')
        ->set('amount', 50)
        ->set('firstName', 'Test Donor')
        ->set('email', 'attr@example.com')
        ->call('submit')
        ->assertHasNoErrors();

    $donation = Donation::query()->whereHas('donor', fn ($q) => $q->where('email', 'attr@example.com'))->firstOrFail();

    expect($donation->utm_params)->toMatchArray([
        'utm_source' => 'facebook',
        'utm_medium' => 'cpc',
        'utm_campaign' => 'ramadan-2026',
        'fbclid' => 'abc123',
        'gclid' => 'xyz789',
    ]);
});

it('sends browser and click identifiers with the purchase conversion event', function () {
    Http::fake([
        'graph.facebook.com/*' => Http::response(['events_received' => 1], 200),
    ]);

    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create([
        'email' => 'Donor@Example.com',
        'first_name' => "O'Brien",
        'last_name' => 'Tan',
        'phone' => '+60 12-345 6789',
    ]);
    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'gross_amount' => 100.00,
        'currency' => 'myr',
        'status' => DonationStatus::Succeeded,
        'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
        'browser' => 'Chrome',
        'fbp' => 'fb.1.1700000000000.1234567890',
        'fbc' => 'fb.1.1700000000000.IwAR-click',
        'billing_country' => 'MY',
    ]);

    TrackingConfiguration::factory()->for($organization)->meta([
        'pixel_id' => '123456789012345',
        'access_token' => 'test-access-token',
    ])->create();

    (new SendMetaConversionEvent($donation))->handle();

    $userData = TrackingEvent::query()
        ->where('donation_id', $donation->id)
        ->firstOrFail()
        ->payload['user_data'];

    expect($userData['client_user_agent'])->toBe('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36')
        ->and($userData['fbp'])->toBe('fb.1.1700000000000.1234567890')
        ->and($userData['fbc'])->toBe('fb.1.1700000000000.IwAR-click')
        ->and($userData['em'])->toBe(hash('sha256', 'donor@example.com'))
        ->and($userData['external_id'])->toBe(hash('sha256', 'donor@example.com'))
        ->and($userData['ph'])->toBe(hash('sha256', '60123456789'))
        ->and($userData['fn'])->toBe(hash('sha256', 'obrien'))
        ->and($userData['ln'])->toBe(hash('sha256', 'tan'))
        ->and($userData['country'])->toBe(hash('sha256', 'my'));
});

it('rebuilds fbc from fbclid using a millisecond timestamp', function () {
    Http::fake(['graph.facebook.com/*' => Http::response(['events_received' => 1], 200)]);

    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donation = Donation::factory()->for($campaign)->create([
        'gross_amount' => 25.00,
        'currency' => 'myr',
        'status' => DonationStatus::Succeeded,
        'fbc' => null,
        'utm_params' => ['fbclid' => 'clickabc'],
    ]);

    TrackingConfiguration::factory()->for($organization)->meta([
        'pixel_id' => '123456789012345',
        'access_token' => 'token',
    ])->create();

    (new SendMetaConversionEvent($donation))->handle();

    $fbc = TrackingEvent::query()->where('donation_id', $donation->id)->firstOrFail()->payload['user_data']['fbc'];

    expect($fbc)->toBe('fb.1.'.($donation->created_at->timestamp * 1000).'.clickabc');
});

it('pairs the converted value with the base currency it is stored in', function () {
    Http::fake(['graph.facebook.com/*' => Http::response(['events_received' => 1], 200)]);

    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donation = Donation::factory()->for($campaign)->create([
        'gross_amount' => 100.00,
        'currency' => 'usd',
        'base_amount' => 470.00,
        'base_currency' => 'myr',
        'status' => DonationStatus::Succeeded,
    ]);

    TrackingConfiguration::factory()->for($organization)->meta([
        'pixel_id' => '123456789012345',
        'access_token' => 'token',
    ])->create();

    (new SendMetaConversionEvent($donation))->handle();

    $payload = TrackingEvent::query()->where('donation_id', $donation->id)->firstOrFail()->payload;

    expect($payload['custom_data']['value'])->toEqual(470.0)
        ->and($payload['custom_data']['currency'])->toBe('MYR');
});

it('reads the meta pixel cookies and the full user agent off the request', function () {
    $request = Request::create('https://ihsan.test/donate/abc', 'GET', [], [
        '_fbp' => 'fb.1.1700000000000.987654321',
        '_fbc' => 'fb.1.1700000000000.IwAR-host-click',
    ], [], ['HTTP_USER_AGENT' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X)']);

    $info = ClientInfo::fromRequest($request);

    expect($info['fbp'])->toBe('fb.1.1700000000000.987654321')
        ->and($info['fbc'])->toBe('fb.1.1700000000000.IwAR-host-click')
        ->and($info['user_agent'])->toBe('Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X)')
        ->and($info['browser'])->not->toBe($info['user_agent']);
});

it('keeps the meta cookies readable through the encrypt cookies middleware', function () {
    expect(EncryptCookies::serialized('_fbp'))->toBeFalse();

    $middleware = app(EncryptCookies::class);
    $request = Request::create('/', 'GET', [], ['_fbp' => 'fb.1.1700000000000.987654321']);

    $middleware->handle($request, function (Request $r) use (&$seen): Response {
        $seen = $r->cookie('_fbp');

        return new Response;
    });

    expect($seen)->toBe('fb.1.1700000000000.987654321');
});

it('attributes the donation to the host page when the widget forwards it', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create([
        'suggested_amounts' => [50, 100, 200],
    ]);
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'type' => ElementType::Form,
        'config' => ['default_amount' => 50, 'allow_monthly' => false],
    ]);

    $this->mock(CreatePaymentIntent::class, function ($mock): void {
        $mock->shouldReceive('create')->once()->andReturn(PaymentIntent::constructFrom([
            'id' => 'pi_host_page_test',
            'client_secret' => 'pi_host_page_test_secret',
        ]));
    });

    Livewire::withQueryParams(['pu' => 'https://donor-site.test/appeal?utm_source=facebook&fbclid=hostclick'])
        ->test(DonationForm::class, ['element' => $element])
        ->set('frequency', 'one_time')
        ->set('amount', 50)
        ->set('firstName', 'Host Donor')
        ->set('email', 'host@example.com')
        ->call('submit')
        ->assertHasNoErrors();

    $donation = Donation::query()->whereHas('donor', fn ($q) => $q->where('email', 'host@example.com'))->firstOrFail();

    expect($donation->page_url)->toBe('https://donor-site.test/appeal?utm_source=facebook&fbclid=hostclick')
        ->and($donation->utm_params['fbclid'])->toBe('hostclick')
        ->and($donation->utm_params['utm_source'])->toBe('facebook')
        ->and($donation->fbc)->toStartWith('fb.1.')
        ->and($donation->fbc)->toEndWith('.hostclick');
});

it('ignores a host page url that is not an absolute http address', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create([
        'suggested_amounts' => [50, 100, 200],
    ]);
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'type' => ElementType::Form,
        'config' => ['default_amount' => 50, 'allow_monthly' => false],
    ]);

    $component = Livewire::withQueryParams(['pu' => 'javascript:alert(1)'])
        ->test(DonationForm::class, ['element' => $element]);

    $component->assertSet('parentPageUrl', '');
});
