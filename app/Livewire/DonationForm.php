<?php

namespace App\Livewire;

use App\Actions\Stripe\CreatePaymentIntent;
use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Enums\ElementType;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Element;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.donation')]
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

    public bool $submitted = false;

    public string $stripeClientSecret = '';

    public function mount(Element $element): void
    {
        abort_if(
            ! $element->is_active || $element->type !== ElementType::Form || $element->campaign === null,
            404
        );

        $this->element = $element->loadMissing(['campaign.organization']);
        $this->amount = $this->config('default_amount', $this->suggestedAmounts()[0] ?? 5);
        $this->frequency = $this->config('default_frequency', $this->config('allow_monthly', true) ? 'monthly' : 'one_time');
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

    public function submit(): void
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
            $this->stripeClientSecret = $paymentIntent->client_secret;
            $this->submitted = true;
        } catch (\Exception $e) {
            $donation->update(['status' => DonationStatus::Failed]);
            session()->flash('error', 'Payment could not be processed. Please try again.');
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
    public function suggestedAmounts(): array
    {
        $amounts = $this->config('suggested_amounts');

        if (! is_array($amounts) || $amounts === []) {
            $amounts = $this->element->campaign?->suggested_amounts;
        }

        if (! is_array($amounts) || $amounts === []) {
            $amounts = [200, 100, 50, 30, 10, 5];
        }

        return collect($amounts)
            ->map(fn (mixed $amount): int => (int) $amount)
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
        return view('livewire.donation-form');
    }
}
