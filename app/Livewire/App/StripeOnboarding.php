<?php

declare(strict_types=1);

namespace App\Livewire\App;

use App\Models\User;
use App\Services\AuditLogLogger;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Stripe\Account;
use Stripe\Stripe;

#[Layout('layouts.app')]
class StripeOnboarding extends Component
{
    public bool $showSuccessModal = false;

    public bool $alreadyConnected = false;

    public function mount(): void
    {
        $org = Auth::user()?->organization;

        if ($org && request()->query('onboarding') === 'success') {
            $this->verifyAndShowSuccessModal($org);

            return;
        }

        if ($org && $org->stripe_onboarded) {
            $this->alreadyConnected = true;
        }
    }

    private function verifyAndShowSuccessModal($org): void
    {
        if ($org && ! $org->stripe_onboarded && $org->stripe_account_id) {
            Stripe::setApiKey(config('services.stripe.secret'));

            try {
                $account = Account::retrieve($org->stripe_account_id);

                if ($account->charges_enabled) {
                    $org->update([
                        'stripe_onboarded' => true,
                        'stripe_onboarded_at' => now(),
                    ]);

                    AuditLogLogger::stripeOnboardingCompleted(
                        $org,
                        $this->getUser(),
                        $org->stripe_account_id,
                    );
                }
            } catch (\Throwable) {
                // Ignore Stripe errors and leave the onboarding page visible.
            }
        }

        if ($org && $org->stripe_onboarded) {
            $this->showSuccessModal = true;
        }
    }

    public function closeSuccessModal(): void
    {
        $this->redirect(route('app.campaigns.index'));
    }

    public function closeAlreadyConnectedModal(): void
    {
        $this->redirect(route('app.dashboard'));
    }

    public function goToStripeSettings(): void
    {
        $this->redirect(route('app.settings.payment'));
    }

    public function connect(): void
    {
        $org = Auth::user()?->organization;

        if (! $org) {
            return;
        }

        if ($org->stripe_onboarded) {
            $this->redirect(route('app.dashboard'));

            return;
        }

        $this->redirect(route('stripe.connect.redirect'), navigate: false);
    }

    public function render()
    {
        return view('livewire.app.stripe-onboarding', ['title' => 'Stripe Connect Onboarding']);
    }

    private function getUser(): ?User
    {
        return Auth::user();
    }
}
