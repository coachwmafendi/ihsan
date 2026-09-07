<?php

declare(strict_types=1);

use App\Actions\Stripe\FetchPaymentMethodDomainStatuses;
use App\Actions\Stripe\RegisterPaymentMethodDomains;
use App\Jobs\RegisterStripePaymentMethodDomains;
use App\Livewire\App\Settings\AllowDomains;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Stripe\Exception\InvalidRequestException;
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

it('shows whether Stripe verified the checkout frame itself', function () {
    // Safari only allows Apple Pay in a cross-origin iframe when the frame's own
    // source domain is registered, so an organiser whose site verifies fine can
    // still lose the wallet with nothing on screen explaining why.
    $org = Organization::factory()->stripeConnected()->create([
        'settings' => ['allowed_domains' => ['tahfizannur.org']],
    ]);
    $user = User::factory()->create(['organization_id' => $org->id]);

    $this->swap(FetchPaymentMethodDomainStatuses::class, new FetchPaymentMethodDomainStatuses(
        fakeStripeClientForStatuses([
            ['domain' => 'tahfizannur.org', 'apple' => 'active', 'google' => 'active'],
            ['domain' => 'app.getihsan.my', 'apple' => 'inactive', 'google' => 'inactive', 'error' => 'Domain verification file not reachable'],
        ])
    ));

    $component = Livewire::actingAs($user)->test(AllowDomains::class)->call('loadDomainStatuses');

    expect($component->instance()->checkoutDomain())->toBe('app.getihsan.my');

    $component->assertSee('app.getihsan.my')
        ->assertSee('Ihsan checkout')
        ->assertSee('Not verified')
        ->assertSee('Domain verification file not reachable');
});

it('says so when Ihsan itself has no checkout domain configured', function () {
    // Nothing gets registered in that case, so the wallet is missing for every
    // organisation at once and the cause is ours rather than theirs.
    config()->set('app.app_panel_domain', null);

    $org = Organization::factory()->stripeConnected()->create([
        'settings' => ['allowed_domains' => ['tahfizannur.org']],
    ]);
    $user = User::factory()->create(['organization_id' => $org->id]);

    $this->swap(FetchPaymentMethodDomainStatuses::class, new FetchPaymentMethodDomainStatuses(
        fakeStripeClientForStatuses([])
    ));

    Livewire::actingAs($user)->test(AllowDomains::class)
        ->call('loadDomainStatuses')
        ->assertSee('Not configured')
        ->assertSee('no checkout domain configured');
});

/**
 * Build a StripeClient whose create() refuses with a Stripe API error.
 */
function fakeStripeClientRefusingDomains(string $message): StripeClient
{
    $service = Mockery::mock();
    $service->shouldReceive('all')->andReturn(new class
    {
        /** @var array<int, object> */
        public array $data = [];
    });
    $service->shouldReceive('create')->andThrow(new InvalidRequestException($message));

    $client = Mockery::mock(StripeClient::class);
    $client->paymentMethodDomains = $service;

    return $client;
}

it('keeps the reason Stripe refused a domain', function () {
    // Stripe will not register a domain whose verification file it cannot
    // fetch, so the domain is absent from its records entirely and "pending"
    // is a misreading - it is never going to arrive on its own.
    $org = Organization::factory()->stripeConnected()->create([
        'settings' => ['allowed_domains' => ['mtaqlaa.onpay.my']],
    ]);

    (new RegisterPaymentMethodDomains(
        fakeStripeClientRefusingDomains('The domain could not be verified.')
    ))->register($org);

    expect($org->fresh()->settings['payment_domain_errors']['mtaqlaa.onpay.my'])
        ->toContain('could not be verified');
});

it('shows the refusal instead of calling a rejected domain pending', function () {
    $org = Organization::factory()->stripeConnected()->create([
        'settings' => [
            'allowed_domains' => ['mtaqlaa.onpay.my'],
            'payment_domain_errors' => ['mtaqlaa.onpay.my' => 'The domain could not be verified.'],
        ],
    ]);
    $user = User::factory()->create(['organization_id' => $org->id]);

    $this->swap(FetchPaymentMethodDomainStatuses::class, new FetchPaymentMethodDomainStatuses(
        fakeStripeClientForStatuses([])
    ));

    $component = Livewire::actingAs($user)->test(AllowDomains::class)
        ->call('loadDomainStatuses')
        ->assertSee('Not registered')
        ->assertSee('The domain could not be verified.');

    // Pending still belongs to a domain we simply have not heard back about.
    expect($component->instance()->walletStatusFor('mtaqlaa.onpay.my'))
        ->label->toBe('Not registered')
        ->tone->toBe('failed');
});

it('clears a stored refusal once the domain registers', function () {
    $created = [];

    $org = Organization::factory()->stripeConnected()->create([
        'settings' => [
            'allowed_domains' => ['mtaqlaa.onpay.my'],
            'payment_domain_errors' => ['mtaqlaa.onpay.my' => 'The domain could not be verified.'],
        ],
    ]);

    (new RegisterPaymentMethodDomains(fakeStripeClientForDomains($created)))->register($org);

    expect($org->fresh()->settings['payment_domain_errors'])->toBe([]);
});

it('points out a subdomain donations arrive from that nobody registered', function () {
    // The parent domain being verified is no help: Stripe registers domains
    // exactly, so give.example.org loses its wallets while example.org is green.
    $org = Organization::factory()->stripeConnected()->create([
        'settings' => ['allowed_domains' => ['onpay.my']],
    ]);
    $user = User::factory()->create(['organization_id' => $org->id]);
    $campaign = Campaign::factory()->for($org)->create();

    Donation::factory()->for($campaign)->create(['page_url' => 'https://mtaqlaa.onpay.my/order/form/infaq-overseas']);
    Donation::factory()->for($campaign)->create(['page_url' => 'https://onpay.my/give']);

    $this->swap(FetchPaymentMethodDomainStatuses::class, new FetchPaymentMethodDomainStatuses(
        fakeStripeClientForStatuses([])
    ));

    $component = Livewire::actingAs($user)->test(AllowDomains::class);

    expect($component->instance()->unregisteredEmbeddingDomains())->toBe(['mtaqlaa.onpay.my']);

    $component->assertSee('Donations are coming from domains you have not added')
        ->assertSee('mtaqlaa.onpay.my');
});

it('does not flag the checkout domain or a domain already added', function () {
    config()->set('app.app_panel_domain', 'app.getihsan.my');

    $org = Organization::factory()->stripeConnected()->create([
        'settings' => ['allowed_domains' => ['tahfizannur.org']],
    ]);
    $user = User::factory()->create(['organization_id' => $org->id]);
    $campaign = Campaign::factory()->for($org)->create();

    Donation::factory()->for($campaign)->create(['page_url' => 'https://www.tahfizannur.org/derma']);
    Donation::factory()->for($campaign)->create(['page_url' => 'https://app.getihsan.my/donate/abc']);

    $this->swap(FetchPaymentMethodDomainStatuses::class, new FetchPaymentMethodDomainStatuses(
        fakeStripeClientForStatuses([])
    ));

    expect(Livewire::actingAs($user)->test(AllowDomains::class)->instance()->unregisteredEmbeddingDomains())
        ->toBe([]);
});
