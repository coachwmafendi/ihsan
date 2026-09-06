<?php

declare(strict_types=1);

use App\Actions\Stripe\CreatePaymentIntent;
use App\Enums\CampaignStatus;
use App\Enums\DonationStatus;
use App\Enums\ElementType;
use App\Livewire\DonationForm;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Element;
use App\Models\Organization;
use Livewire\Livewire;
use Stripe\ApiRequestor;
use Stripe\HttpClient\ClientInterface;
use Stripe\HttpClient\CurlClient;
use Stripe\PaymentIntent;
use Stripe\Stripe;

/**
 * A donor who double-clicks, or retries after a slow response, used to leave a
 * second pending donation and a second PaymentIntent behind. Nothing charged
 * twice, but the records pile up and the organiser cannot tell an abandoned
 * checkout from a duplicate.
 */
beforeEach(function () {
    Stripe::setApiKey('sk_test_dedupe');
    config(['services.stripe.secret' => 'sk_test_dedupe']);

    $this->organization = Organization::factory()->create();
    $this->campaign = Campaign::factory()
        ->for($this->organization)
        ->create(['status' => CampaignStatus::Active]);
    $this->element = Element::factory()
        ->for($this->organization)
        ->for($this->campaign)
        ->create([
            'type' => ElementType::Form,
            'config' => ['allow_monthly' => true, 'allow_cover_fee' => true],
        ]);
});

afterEach(function () {
    ApiRequestor::setHttpClient(CurlClient::instance());
});

function fakeStripeIntentStatus(string $status): void
{
    ApiRequestor::setHttpClient(new class($status) implements ClientInterface
    {
        public function __construct(private string $status) {}

        public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null)
        {
            return [json_encode([
                'id' => 'pi_dedupe',
                'object' => 'payment_intent',
                'status' => $this->status,
                'client_secret' => 'pi_dedupe_secret',
            ]), 200, []];
        }
    });
}

function submitDonation(Element $element, float $amount = 100): void
{
    Livewire::test(DonationForm::class, ['element' => $element])
        ->set('amount', $amount)
        ->set('coverFee', false)
        ->set('frequency', 'one_time')
        ->set('firstName', 'Ahmad Donor')
        ->set('email', 'ahmad@example.com')
        ->call('submit');
}

it('reuses the pending donation when the donor submits the same thing twice', function () {
    $this->mock(CreatePaymentIntent::class)
        ->shouldReceive('create')
        ->once()
        ->andReturn(PaymentIntent::constructFrom([
            'id' => 'pi_dedupe',
            'client_secret' => 'pi_dedupe_secret',
            'status' => 'requires_payment_method',
        ]));

    fakeStripeIntentStatus('requires_payment_method');

    submitDonation($this->element);
    submitDonation($this->element);

    // One row, one PaymentIntent: the mock above allows exactly one create().
    expect(Donation::query()->count())->toBe(1)
        ->and(Donation::query()->sole()->stripe_payment_intent_id)->toBe('pi_dedupe');
});

it('starts a new donation when the amount changed', function () {
    $this->mock(CreatePaymentIntent::class)
        ->shouldReceive('create')
        ->twice()
        ->andReturnUsing(fn (): PaymentIntent => PaymentIntent::constructFrom([
            'id' => 'pi_'.uniqid(),
            'client_secret' => 'pi_dedupe_secret',
            'status' => 'requires_payment_method',
        ]));

    submitDonation($this->element, 100);
    submitDonation($this->element, 250);

    expect(Donation::query()->count())->toBe(2);
});

it('starts a new donation once the earlier attempt has gone stale', function () {
    $this->mock(CreatePaymentIntent::class)
        ->shouldReceive('create')
        ->twice()
        ->andReturnUsing(fn (): PaymentIntent => PaymentIntent::constructFrom([
            'id' => 'pi_'.uniqid(),
            'client_secret' => 'pi_dedupe_secret',
            'status' => 'requires_payment_method',
        ]));

    submitDonation($this->element);

    Donation::query()->update(['created_at' => now()->subHours(2)]);

    submitDonation($this->element);

    expect(Donation::query()->count())->toBe(2);
});

it('starts a new donation when the earlier payment intent can no longer be paid', function () {
    $this->mock(CreatePaymentIntent::class)
        ->shouldReceive('create')
        ->twice()
        ->andReturnUsing(fn (): PaymentIntent => PaymentIntent::constructFrom([
            'id' => 'pi_'.uniqid(),
            'client_secret' => 'pi_dedupe_secret',
            'status' => 'requires_payment_method',
        ]));

    submitDonation($this->element);

    // The donor got as far as authorising, then came back and submitted again.
    fakeStripeIntentStatus('succeeded');

    submitDonation($this->element);

    expect(Donation::query()->count())->toBe(2);
});

it('leaves another donor of the same campaign alone', function () {
    $this->mock(CreatePaymentIntent::class)
        ->shouldReceive('create')
        ->twice()
        ->andReturnUsing(fn (): PaymentIntent => PaymentIntent::constructFrom([
            'id' => 'pi_'.uniqid(),
            'client_secret' => 'pi_dedupe_secret',
            'status' => 'requires_payment_method',
        ]));

    submitDonation($this->element);

    Livewire::test(DonationForm::class, ['element' => $this->element])
        ->set('amount', 100)
        ->set('coverFee', false)
        ->set('frequency', 'one_time')
        ->set('firstName', 'Siti Donor')
        ->set('email', 'siti@example.com')
        ->call('submit');

    expect(Donation::query()->count())->toBe(2)
        ->and(Donation::query()->where('status', DonationStatus::Pending)->count())->toBe(2);
});
