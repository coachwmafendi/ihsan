<?php

declare(strict_types=1);

namespace App\Livewire\App\Settings;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Stripe\Account as StripeAccount;
use Stripe\Stripe;

#[Layout('layouts.app')]
class Payment extends Component
{
    public array $currencies = ['myr' => true, 'usd' => false, 'sgd' => false];

    public string $feeCollectionMethod = 'invoice';

    public bool $showReconnectConfirm = false;

    public function mount(): void
    {
        $org = Auth::user()?->organization;
        $settings = $org?->settings ?? [];
        $accepted = $settings['accepted_currencies'] ?? ['myr'];

        $this->currencies = [
            'myr' => true,
            'usd' => in_array('usd', $accepted),
            'sgd' => in_array('sgd', $accepted),
        ];

        $this->feeCollectionMethod = $org?->fee_collection_method ?? 'invoice';
    }

    public function updatedCurrencies(): void
    {
        $org = Auth::user()?->organization;
        if (! $org) {
            return;
        }

        $accepted = ['myr'];
        if ($this->currencies['usd']) {
            $accepted[] = 'usd';
        }
        if ($this->currencies['sgd']) {
            $accepted[] = 'sgd';
        }

        $settings = array_merge($org->settings ?? [], ['accepted_currencies' => $accepted]);
        $org->update(['settings' => $settings]);
        $this->dispatch('notify', message: 'Accepted currencies updated.', variant: 'success');
    }

    public function saveFeeCollection(): void
    {
        $org = Auth::user()?->organization;
        if (! $org) {
            return;
        }

        $org->update(['fee_collection_method' => $this->feeCollectionMethod]);
        $this->dispatch('notify', message: 'Fee collection method updated.', variant: 'success');
    }

    public function reconnect(): void
    {
        $org = Auth::user()?->organization;
        if (! $org) {
            return;
        }

        $org->update(['stripe_account_id' => null, 'stripe_onboarded' => false]);
        $this->redirect('/app/stripe-onboarding');
    }

    public function getProcessingFeePercent(): string
    {
        return number_format((float) config('services.stripe.processing_fee_percent', 2.5), 1);
    }

    public function stripeAccount(): ?StripeAccount
    {
        $org = Auth::user()?->organization;
        if (! $org || ! $org->stripe_account_id) {
            return null;
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        try {
            return StripeAccount::retrieve($org->stripe_account_id);
        } catch (\Throwable) {
            return null;
        }
    }

    public function render()
    {
        return view('livewire.app.settings.payment', ['title' => 'Settings — Payment']);
    }
}
