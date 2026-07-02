<?php

declare(strict_types=1);

namespace App\Livewire\App\Settings;

use App\Actions\Chip\CreateWebhook;
use App\Models\User;
use App\Services\AuditLogLogger;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Stripe\Account as StripeAccount;
use Stripe\Stripe;
use Throwable;

#[Layout('layouts.app')]
class Payment extends Component
{
    public array $currencies = ['myr' => true, 'usd' => false, 'sgd' => false];

    public string $feeCollectionMethod = 'invoice';

    public bool $showReconnectConfirm = false;

    public string $activeTab = 'stripe';

    public string $chipBrandId = '';

    public string $chipApiKey = '';

    public string $chipWebhookId = '';

    public string $chipWebhookPublicKey = '';

    public array $chipPaymentMethods = ['card'];

    public bool $showChipApiKey = false;

    public bool $stripeEnabled = true;

    public bool $chipEnabled = true;

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
        $this->chipBrandId = $org?->chip_brand_id ?? '';
        $this->chipApiKey = $org?->chip_api_key ?? '';
        $this->chipWebhookId = $org?->chip_webhook_id ?? '';
        $this->chipWebhookPublicKey = $org?->chip_webhook_public_key ?? '';
        $this->chipPaymentMethods = $org?->chipPaymentMethods() ?? ['card'];
        $this->stripeEnabled = $org?->stripe_enabled ?? true;
        $this->chipEnabled = $org?->chip_enabled ?? true;
    }

    public function toggleStripeEnabled(): void
    {
        $org = Auth::user()?->organization;
        if (! $org) {
            return;
        }

        $org->update(['stripe_enabled' => $this->stripeEnabled]);
        $this->dispatch('notify', message: $this->stripeEnabled ? 'Stripe enabled for checkout.' : 'Stripe disabled for checkout.', variant: 'success');
    }

    public function toggleChipEnabled(): void
    {
        $org = Auth::user()?->organization;
        if (! $org) {
            return;
        }

        $org->update(['chip_enabled' => $this->chipEnabled]);
        $this->dispatch('notify', message: $this->chipEnabled ? 'CHIP enabled for checkout.' : 'CHIP disabled for checkout.', variant: 'success');
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

        AuditLogLogger::stripeDisconnected($org, $this->getUser());

        $this->redirect('/app/stripe-onboarding');
    }

    public function saveChipSettings(): void
    {
        $org = Auth::user()?->organization;
        if (! $org) {
            return;
        }

        $this->validate([
            'chipBrandId' => ['nullable', 'string', 'max:255'],
            'chipApiKey' => ['nullable', 'string', 'max:255'],
            'chipWebhookId' => ['nullable', 'string', 'max:255'],
            'chipWebhookPublicKey' => ['nullable', 'string', 'max:2000'],
            'chipPaymentMethods' => ['required', 'array', 'min:1'],
            'chipPaymentMethods.*' => ['in:card,fpx'],
        ], [
            'chipPaymentMethods.required' => 'Select at least one payment method.',
        ]);

        $settings = array_merge($org->settings ?? [], [
            'chip_payment_methods' => $this->chipPaymentMethods,
        ]);

        $org->update([
            'chip_brand_id' => $this->chipBrandId ?: null,
            'chip_api_key' => $this->chipApiKey ?: null,
            'chip_webhook_id' => $this->chipWebhookId ?: null,
            'chip_webhook_public_key' => $this->chipWebhookPublicKey ?: null,
            'settings' => $settings,
        ]);

        if ($org->chip_onboarded && blank($org->chip_webhook_id) && blank($org->chip_webhook_public_key)) {
            try {
                app(CreateWebhook::class)->create($org);
            } catch (Throwable $e) {
                $this->dispatch('notify', message: 'CHIP settings saved, but webhook registration failed: '.$e->getMessage(), variant: 'warning');

                return;
            }
        }

        $this->dispatch('notify', message: 'CHIP settings saved.', variant: 'success');
    }

    public function toggleChipApiKey(): void
    {
        $this->showChipApiKey = ! $this->showChipApiKey;
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
        } catch (Throwable) {
            return null;
        }
    }

    public function render()
    {
        return view('livewire.app.settings.payment', ['title' => 'Settings — Payment Processors']);
    }

    private function getUser(): ?User
    {
        return Auth::user();
    }
}
