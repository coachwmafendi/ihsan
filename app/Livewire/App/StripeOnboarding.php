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
            $this->redirect('/app/dashboard');
        }
    }

    public function getOnboardingUrl(): ?string
    {
        $org = Auth::user()?->organization;
        if (! $org || ! $org->stripe_account_id) return null;

        try {
            return app(CreateConnectAccount::class)->generateOnboardingLink($org);
        } catch (\Throwable) {
            return null;
        }
    }

    public function render()
    {
        return view('livewire.app.stripe-onboarding', ['title' => 'Stripe Connect Onboarding']);
    }
}
