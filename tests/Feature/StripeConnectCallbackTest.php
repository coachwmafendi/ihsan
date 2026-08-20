<?php

use App\Jobs\RegisterStripePaymentMethodDomains;
use App\Models\Organization;
use App\Services\StripeOAuthClient;
use Illuminate\Support\Facades\Queue;
use Mockery\MockInterface;
use Stripe\StripeObject;

beforeEach(function () {
    Queue::fake();

    $this->organization = Organization::factory()->withoutStripe()->create();
    $this->state = 'test_state_'.random_int(1000, 9999);
});

afterEach(function () {
    Mockery::close();
});

function mockStripeOAuth(string $stripeUserId): MockInterface
{
    return test()->mock(StripeOAuthClient::class, function (MockInterface $mock) use ($stripeUserId): void {
        $mock->shouldReceive('token')->andReturn(StripeObject::constructFrom([
            'stripe_user_id' => $stripeUserId,
        ]));
    });
}

it('connects a stripe account and redirects to payment settings', function () {
    $stripeUserId = 'acct_'.fake()->regexify('[A-Za-z0-9]{10}');

    mockStripeOAuth($stripeUserId);

    $response = $this->withSession([
        'stripe_connect_state' => $this->state,
        'stripe_connect_org_id' => $this->organization->id,
    ])->get("https://app.example.test/stripe/connect/callback?code=code_123&state={$this->state}");

    $response->assertRedirect('https://app.example.test/settings/payment');
    $response->assertSessionHas('stripe_connect_success');

    $this->organization->refresh();

    expect($this->organization->stripe_account_id)->toBe($stripeUserId)
        ->and($this->organization->stripe_onboarded)->toBeTrue();

    Queue::assertPushed(RegisterStripePaymentMethodDomains::class);
});

it('redirects with an error when stripe returns an error parameter', function () {
    $response = $this->get('https://app.example.test/stripe/connect/callback?error=access_denied');

    $response->assertRedirect('https://app.example.test/stripe-onboarding');
    $response->assertSessionHas('error', fn ($message) => str_contains($message, 'cancelled'));
});

it('redirects with an error when code or state is missing', function () {
    $response = $this->get('https://app.example.test/stripe/connect/callback?code=code_123');

    $response->assertRedirect('https://app.example.test/stripe-onboarding');
    $response->assertSessionHas('error', fn ($message) => str_contains($message, 'complete'));
});

it('redirects with an error when state does not match session', function () {
    $response = $this->withSession([
        'stripe_connect_state' => $this->state,
    ])->get('https://app.example.test/stripe/connect/callback?code=code_123&state=wrong_state');

    $response->assertRedirect('https://app.example.test/stripe-onboarding');
    $response->assertSessionHas('error', fn ($message) => str_contains($message, 'session'));
});

it('redirects with an error when organization is not found', function () {
    $response = $this->withSession([
        'stripe_connect_state' => $this->state,
        'stripe_connect_org_id' => 999999,
    ])->get("https://app.example.test/stripe/connect/callback?code=code_123&state={$this->state}");

    $response->assertRedirect('https://app.example.test/stripe-onboarding');
    $response->assertSessionHas('error', fn ($message) => str_contains($message, 'Organization'));
});

it('redirects with an error when stripe oauth token exchange fails', function () {
    $this->mock(StripeOAuthClient::class, function (MockInterface $mock): void {
        $mock->shouldReceive('token')->andThrow(new Exception('Stripe API error'));
    });

    $response = $this->withSession([
        'stripe_connect_state' => $this->state,
        'stripe_connect_org_id' => $this->organization->id,
    ])->get("https://app.example.test/stripe/connect/callback?code=code_123&state={$this->state}");

    $response->assertRedirect('https://app.example.test/stripe-onboarding');
    $response->assertSessionHas('error', fn ($message) => str_contains($message, 'could not complete'));
});

it('redirects with an error when stripe does not return an account identifier', function () {
    $this->mock(StripeOAuthClient::class, function (MockInterface $mock): void {
        $mock->shouldReceive('token')->andReturn(StripeObject::constructFrom(['stripe_user_id' => null]));
    });

    $response = $this->withSession([
        'stripe_connect_state' => $this->state,
        'stripe_connect_org_id' => $this->organization->id,
    ])->get("https://app.example.test/stripe/connect/callback?code=code_123&state={$this->state}");

    $response->assertRedirect('https://app.example.test/stripe-onboarding');
    $response->assertSessionHas('error', fn ($message) => str_contains($message, 'account identifier'));
});

it('redirects with an informative error when stripe account is already connected to another organization', function () {
    $stripeUserId = 'acct_existing123';

    Organization::factory()->withoutStripe()->create([
        'stripe_account_id' => $stripeUserId,
    ]);

    mockStripeOAuth($stripeUserId);

    $response = $this->withSession([
        'stripe_connect_state' => $this->state,
        'stripe_connect_org_id' => $this->organization->id,
    ])->get("https://app.example.test/stripe/connect/callback?code=code_123&state={$this->state}");

    $response->assertRedirect('https://app.example.test/stripe-onboarding');
    $response->assertSessionHas('error', fn ($message) => str_contains($message, 'already connected to another Ihsan organization'));

    $this->organization->refresh();

    expect($this->organization->stripe_account_id)->toBeNull();
    Queue::assertNothingPushed();
});
