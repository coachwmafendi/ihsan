<?php

declare(strict_types=1);

use App\Actions\Stripe\FetchPaymentMethodDomainStatuses;
use App\Actions\Stripe\RegisterPaymentMethodDomains;
use App\Jobs\RegisterStripePaymentMethodDomains;
use App\Livewire\App\Settings\AllowDomains;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Stripe\StripeClient;

uses(RefreshDatabase::class);

beforeEach(function () {
    config()->set('app.app_panel_domain', 'app.getihsan.my');
    config()->set('services.stripe.secret', 'sk_test_dummy');
});

/**
 * Build a StripeClient whose paymentMethodDomains service records calls.
 *
 * @param  array<int, string>  $alreadyRegistered
 */
function fakeStripeClientForDomains(array &$created, array $alreadyRegistered = []): StripeClient
{
    $service = Mockery::mock();

    $service->shouldReceive('all')->andReturnUsing(function (array $params) use ($alreadyRegistered) {
        $domain = $params['domain_name'];
        $data = in_array($domain, $alreadyRegistered, true)
            ? [(object) ['id' => 'pmd_'.md5($domain)]]
            : [];

        return new class($data)
        {
            /** @param array<int, object> $data */
            public function __construct(public array $data) {}
        };
    });

    $service->shouldReceive('create')->andReturnUsing(function (array $params) use (&$created) {
        $created[] = $params['domain_name'];

        return (object) ['id' => 'pmd_new', 'domain_name' => $params['domain_name']];
    });

    $service->shouldReceive('validate')->andReturn((object) ['id' => 'pmd_validated']);

    $client = Mockery::mock(StripeClient::class);
    $client->paymentMethodDomains = $service;

    return $client;
}

it('registers the panel domain plus allowed domains on the connected account', function () {
    $created = [];

    $org = Organization::factory()->stripeConnected()->create([
        'settings' => ['allowed_domains' => ['infaq.darulmujtaba.my', 'www.example.org']],
    ]);

    $registered = (new RegisterPaymentMethodDomains(fakeStripeClientForDomains($created)))->register($org);

    expect($registered)->toEqualCanonicalizing([
        'app.getihsan.my',
        'infaq.darulmujtaba.my',
        'example.org',
    ]);

    // www. is normalised away, so example.org is created once.
    expect($created)->toContain('app.getihsan.my', 'infaq.darulmujtaba.my', 'example.org');
});

it('skips creation for domains already registered and revalidates them', function () {
    $created = [];

    $org = Organization::factory()->stripeConnected()->create([
        'settings' => ['allowed_domains' => ['infaq.darulmujtaba.my']],
    ]);

    $registered = (new RegisterPaymentMethodDomains(
        fakeStripeClientForDomains($created, alreadyRegistered: ['app.getihsan.my'])
    ))->register($org);

    expect($registered)->toEqualCanonicalizing(['app.getihsan.my', 'infaq.darulmujtaba.my']);
    expect($created)->toBe(['infaq.darulmujtaba.my']);
});

it('does nothing for organizations without a connected account', function () {
    $created = [];

    $org = Organization::factory()->create(['stripe_account_id' => null]);

    $registered = (new RegisterPaymentMethodDomains(fakeStripeClientForDomains($created)))->register($org);

    expect($registered)->toBe([]);
    expect($created)->toBe([]);
});

it('queues the registration job when allowed domains are saved', function () {
    Queue::fake();

    $org = Organization::factory()->stripeConnected()->create();
    $user = User::factory()->create(['organization_id' => $org->id]);

    Livewire::actingAs($user)
        ->test(AllowDomains::class)
        ->set('allowed_domains', ['infaq.darulmujtaba.my'])
        ->call('save');

    Queue::assertPushed(RegisterStripePaymentMethodDomains::class, function ($job) use ($org) {
        return $job->organizationId === $org->id;
    });
});

it('does not queue the registration job for orgs without a connected account', function () {
    Queue::fake();

    $org = Organization::factory()->create(['stripe_account_id' => null]);
    $user = User::factory()->create(['organization_id' => $org->id]);

    Livewire::actingAs($user)
        ->test(AllowDomains::class)
        ->set('allowed_domains', ['infaq.darulmujtaba.my'])
        ->call('save');

    Queue::assertNothingPushed();
});

/**
 * Build a StripeClient whose paymentMethodDomains->all() returns statuses.
 *
 * @param  array<int, array{domain: string, apple: string, google: string, error?: string}>  $domains
 */
function fakeStripeClientForStatuses(array $domains): StripeClient
{
    $data = array_map(fn (array $d): object => (object) [
        'domain_name' => $d['domain'],
        'apple_pay' => (object) [
            'status' => $d['apple'],
            'status_details' => isset($d['error']) ? (object) ['error_message' => $d['error']] : null,
        ],
        'google_pay' => (object) ['status' => $d['google'], 'status_details' => null],
    ], $domains);

    $service = Mockery::mock();
    $service->shouldReceive('all')->andReturn(new class($data)
    {
        /** @param array<int, object> $data */
        public function __construct(public array $data) {}
    });

    $client = Mockery::mock(StripeClient::class);
    $client->paymentMethodDomains = $service;

    return $client;
}

it('reports a verified domain as having both wallets active', function () {
    $org = Organization::factory()->stripeConnected()->create([
        'settings' => ['allowed_domains' => ['tahfizannur.org']],
    ]);
    $user = User::factory()->create(['organization_id' => $org->id]);

    $this->swap(FetchPaymentMethodDomainStatuses::class, new FetchPaymentMethodDomainStatuses(
        fakeStripeClientForStatuses([
            ['domain' => 'tahfizannur.org', 'apple' => 'active', 'google' => 'active'],
        ])
    ));

    $component = Livewire::actingAs($user)->test(AllowDomains::class)->call('loadDomainStatuses');

    expect($component->instance()->walletStatusFor('tahfizannur.org'))
        ->label->toBe('Wallets active')
        ->tone->toBe('active');
});

it('surfaces why Stripe could not verify a domain', function () {
    // Without the reason the organiser only sees that wallets are missing, which
    // is exactly the state that went unexplained on tahfizannur.org.
    $org = Organization::factory()->stripeConnected()->create([
        'settings' => ['allowed_domains' => ['tahfizannur.org']],
    ]);
    $user = User::factory()->create(['organization_id' => $org->id]);

    $this->swap(FetchPaymentMethodDomainStatuses::class, new FetchPaymentMethodDomainStatuses(
        fakeStripeClientForStatuses([
            ['domain' => 'tahfizannur.org', 'apple' => 'inactive', 'google' => 'inactive', 'error' => 'File not found at /.well-known/'],
        ])
    ));

    $status = Livewire::actingAs($user)
        ->test(AllowDomains::class)
        ->call('loadDomainStatuses')
        ->instance()
        ->walletStatusFor('tahfizannur.org');

    expect($status)
        ->label->toBe('Not verified')
        ->tone->toBe('failed')
        ->error->toBe('File not found at /.well-known/');
});

it('treats a domain Stripe has never seen as pending rather than broken', function () {
    $org = Organization::factory()->stripeConnected()->create([
        'settings' => ['allowed_domains' => ['justadded.org']],
    ]);
    $user = User::factory()->create(['organization_id' => $org->id]);

    $this->swap(FetchPaymentMethodDomainStatuses::class, new FetchPaymentMethodDomainStatuses(
        fakeStripeClientForStatuses([])
    ));

    expect(Livewire::actingAs($user)->test(AllowDomains::class)->call('loadDomainStatuses')->instance()->walletStatusFor('justadded.org'))
        ->tone->toBe('pending');
});

it('matches the status to the domain however the organiser spelled it', function () {
    $org = Organization::factory()->stripeConnected()->create([
        'settings' => ['allowed_domains' => ['https://www.Tahfizannur.org/derma']],
    ]);
    $user = User::factory()->create(['organization_id' => $org->id]);

    $this->swap(FetchPaymentMethodDomainStatuses::class, new FetchPaymentMethodDomainStatuses(
        fakeStripeClientForStatuses([
            ['domain' => 'tahfizannur.org', 'apple' => 'active', 'google' => 'active'],
        ])
    ));

    expect(Livewire::actingAs($user)->test(AllowDomains::class)->call('loadDomainStatuses')->instance()->walletStatusFor('https://www.Tahfizannur.org/derma'))
        ->tone->toBe('active');
});

it('re-registers before reading the statuses back on a recheck', function () {
    // Registration revalidates a domain that previously failed, so a recheck
    // that only re-read the cached statuses would never recover one.
    $created = [];

    $org = Organization::factory()->stripeConnected()->create([
        'settings' => ['allowed_domains' => ['tahfizannur.org']],
    ]);
    $user = User::factory()->create(['organization_id' => $org->id]);

    $this->swap(RegisterPaymentMethodDomains::class, new RegisterPaymentMethodDomains(fakeStripeClientForDomains($created)));
    $this->swap(FetchPaymentMethodDomainStatuses::class, new FetchPaymentMethodDomainStatuses(
        fakeStripeClientForStatuses([
            ['domain' => 'tahfizannur.org', 'apple' => 'active', 'google' => 'active'],
        ])
    ));

    $component = Livewire::actingAs($user)->test(AllowDomains::class)->call('recheckDomains');

    expect($created)->toContain('tahfizannur.org');
    expect($component->instance()->walletStatusFor('tahfizannur.org'))->tone->toBe('active');
});

it('does not hold up the page waiting for Stripe', function () {
    // The statuses load after first paint, so the list renders before the round
    // trip and the badges fill in afterwards.
    $org = Organization::factory()->stripeConnected()->create([
        'settings' => ['allowed_domains' => ['tahfizannur.org']],
    ]);
    $user = User::factory()->create(['organization_id' => $org->id]);

    Livewire::actingAs($user)->test(AllowDomains::class)
        ->assertSet('statuses_loaded', false)
        ->assertSeeHtml('wire:init="loadDomainStatuses"')
        ->assertSee('Checking...');
});

it('does not call Stripe for an organization without a connected account', function () {
    $org = Organization::factory()->create(['stripe_account_id' => null]);
    $user = User::factory()->create(['organization_id' => $org->id]);

    $client = Mockery::mock(StripeClient::class);
    $client->paymentMethodDomains = Mockery::mock()->shouldNotReceive('all')->getMock();

    $this->swap(FetchPaymentMethodDomainStatuses::class, new FetchPaymentMethodDomainStatuses($client));

    Livewire::actingAs($user)->test(AllowDomains::class)
        ->call('loadDomainStatuses')
        ->assertSet('domain_statuses', []);
});
