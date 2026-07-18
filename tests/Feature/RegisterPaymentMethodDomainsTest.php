<?php

declare(strict_types=1);

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
