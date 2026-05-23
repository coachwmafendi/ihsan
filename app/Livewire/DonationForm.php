<?php

namespace App\Livewire;

use App\Actions\Stripe\CreatePaymentIntent;
use App\Actions\Stripe\CreateRecurringSubscription;
use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Enums\ElementType;
use App\Jobs\SendDonationReceipt;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Element;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use Stripe\BalanceTransaction;
use Stripe\PaymentIntent as StripePaymentIntent;
use Stripe\PaymentMethod;
use Stripe\Stripe;

#[Title('Donation Form')]
class DonationForm extends Component
{
    public Element $element;

    public int|float|string $amount = 5;

    public string $frequency = 'monthly';

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public bool $dedicate = false;

    public string $comment = '';

    public bool $isEmbed = false;

    public bool $isPopup = false;

    public function mount(Element $element): void
    {
        abort_if(
            ! $element->is_active || ! in_array($element->type, [ElementType::Form, ElementType::Popup], true) || $element->campaign === null,
            404
        );

        $this->element = $element->loadMissing(['campaign.organization']);
        $this->amount = $this->config('default_amount', $this->suggestedAmounts()[0] ?? 5);
        $this->frequency = $this->config('default_frequency', $this->config('allow_monthly', true) ? 'monthly' : 'one_time');
        $this->isEmbed = request()->query('embed') !== null;
        $this->isPopup = $element->type === ElementType::Popup || request()->query('popup') !== null || (bool) $this->config('display_as_popup', false);
    }

    public function selectAmount(int $amount): void
    {
        $this->amount = $amount;
    }

    public function selectFrequency(string $frequency): void
    {
        if ($frequency === 'monthly' && ! $this->config('allow_monthly', true)) {
            return;
        }

        $this->frequency = $frequency;
    }

    public function confirmPayment(string $paymentIntentId, ?StripePaymentIntent $paymentIntent = null): void
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $donation = Donation::query()
            ->where('stripe_payment_intent_id', $paymentIntentId)
            ->where('status', DonationStatus::Pending)
            ->first();

        if ($donation === null) {
            return;
        }

        try {
            $donation->loadMissing('campaign.organization');
            $stripeOptions = $this->stripeOptionsFor($donation);
            $paymentIntent ??= StripePaymentIntent::retrieve([
                'id' => $paymentIntentId,
                'expand' => ['latest_charge'],
            ], $stripeOptions);

            [$cardBrand, $paymentMethodType] = $this->extractCardDetails($paymentIntent->payment_method, $stripeOptions);

            $charge = $paymentIntent->latest_charge ?? ($paymentIntent->charges->data[0] ?? null);
            $chargeId = is_string($charge) ? $charge : ($charge->id ?? null);
            $stripeFee = 0;
            $balanceTransaction = is_string($charge) ? null : ($charge->balance_transaction ?? null);

            if ($balanceTransaction) {
                $bt = BalanceTransaction::retrieve($balanceTransaction, $stripeOptions);
                $stripeFee = (float) ($bt->fee / 100);
            }

            $platformFee = round((float) $donation->gross_amount * 0.05, 2);

            $donation->update([
                'status' => DonationStatus::Succeeded,
                'stripe_charge_id' => $chargeId,
                'stripe_fee' => $stripeFee,
                'platform_fee' => $platformFee,
                'payment_method_brand' => $cardBrand,
                'payment_method_type' => $paymentMethodType,
                'net_amount' => (float) $donation->gross_amount - $stripeFee - $platformFee,
            ]);

            $donation->campaign()->increment('collected_amount', (float) $donation->gross_amount);

            if ($donation->type === DonationType::Recurring) {
                $subscription = app(CreateRecurringSubscription::class)->create($donation, $paymentIntent, $stripeOptions);
                $donation->update(['subscription_id' => $subscription->getKey()]);
                $subscription->increment('payment_count');
            }

            SendDonationReceipt::dispatch($donation);
        } catch (\Exception $e) {
            // Log error silently
        }
    }

    protected function extractCardDetails(?string $paymentMethodId, array $stripeOptions = []): array
    {
        if ($paymentMethodId === null) {
            return [null, null];
        }

        try {
            $paymentMethod = PaymentMethod::retrieve($paymentMethodId, $stripeOptions);
            $type = $paymentMethod->type;

            if ($type === 'card' && $paymentMethod->card !== null) {
                return [$paymentMethod->card->brand, $type];
            }

            return [$type, $type];
        } catch (\Exception $e) {
            // Silently fail — payment method details are non-critical
        }

        return [null, null];
    }

    /**
     * @return array<string, string>
     */
    private function stripeOptionsFor(Donation $donation): array
    {
        $organization = $donation->campaign?->organization;

        if (! $organization?->stripe_account_id || ! $organization->stripe_onboarded) {
            return [];
        }

        return ['stripe_account' => $organization->stripe_account_id];
    }

    public function submit(): string
    {
        $validated = $this->validate();
        $email = str($validated['email'])->lower()->toString();

        $donor = Donor::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $validated['name'],
                'phone' => $validated['phone'] ?? null,
            ],
        );

        $donation = Donation::query()->create([
            'campaign_id' => $this->element->campaign_id,
            'donor_id' => $donor->getKey(),
            'gross_amount' => $validated['amount'],
            'stripe_fee' => 0,
            'platform_fee' => 0,
            'net_amount' => $validated['amount'],
            'currency' => 'myr',
            'status' => DonationStatus::Pending,
            'type' => $validated['frequency'] === 'monthly' ? DonationType::Recurring : DonationType::OneTime,
            'donor_message' => filled($validated['comment'] ?? null) ? $validated['comment'] : null,
            'is_anonymous' => false,
            'utm_params' => [
                'element_id' => $this->element->getKey(),
                'element_token' => $this->element->token,
                'frequency' => $validated['frequency'],
                'dedicate' => (bool) ($validated['dedicate'] ?? false),
            ],
        ]);

        try {
            $paymentIntent = app(CreatePaymentIntent::class)->create($donation);
            $donation->update(['stripe_payment_intent_id' => $paymentIntent->id]);

            return $paymentIntent->client_secret;
        } catch (\Exception $e) {
            $donation->update(['status' => DonationStatus::Failed]);

            throw $e;
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:1', 'max:100000'],
            'frequency' => [
                'required',
                Rule::in($this->config('allow_monthly', true) ? ['one_time', 'monthly'] : ['one_time']),
            ],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'dedicate' => ['boolean'],
            'comment' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<int, int>
     */
    public function suggestedAmounts(?string $frequency = null): array
    {
        $frequency ??= $this->frequency;

        $amounts = $this->element->campaign?->suggested_amounts;

        if (is_array($amounts) && isset($amounts[$frequency])) {
            $amounts = $amounts[$frequency];
        } else {
            $amounts = $this->element->campaign?->{'suggested_amounts_'.$frequency};
        }

        if (! is_array($amounts) || $amounts === []) {
            $amounts = $this->config('suggested_amounts_'.$frequency);
        }

        if (! is_array($amounts) || $amounts === []) {
            $amounts = $this->config('suggested_amounts');
        }

        if (! is_array($amounts) || $amounts === []) {
            $amounts = [200, 100, 50, 30, 10, 5];
        }

        return collect($amounts)
            ->map(fn (mixed $amount): int => (int) (is_array($amount) ? ($amount['amount'] ?? 0) : $amount))
            ->filter(fn (int $amount): bool => $amount > 0)
            ->unique()
            ->values()
            ->all();
    }

    public function config(string $key, mixed $default = null): mixed
    {
        return data_get($this->element->config ?? [], $key, $default);
    }

    public function render()
    {
        return view('livewire.donation-form')
            ->layout($this->isEmbed ? 'layouts.embed' : ($this->isPopup ? 'layouts.popup' : 'layouts.donation'));
    }
}
