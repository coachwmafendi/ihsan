<?php

declare(strict_types=1);

namespace App\Livewire\App;

use App\Actions\Stripe\CreateConnectAccount;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class StripeOnboarding extends Component
{
    public function mount(): void
    {
        $org = Auth::user()?->organization;
        if ($org && $org->stripe_onboarded) {
            $this->redirect(route('app.dashboard'));
        }
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

        try {
            $service = app(CreateConnectAccount::class);

            if (! $org->stripe_account_id) {
                $service->create($org);
            }

            $url = $service->generateOnboardingLink($org);
        } catch (\Throwable) {
            $this->dispatch('notify', message: 'Unable to start Stripe Connect onboarding. Please try again.', variant: 'danger');

            return;
        }

        $this->redirect($url, navigate: false);
    }

    public function render()
    {
        return view('livewire.app.stripe-onboarding', ['title' => 'Stripe Connect Onboarding']);
    }
}
