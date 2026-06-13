<?php

declare(strict_types=1);

namespace App\Livewire\App\Donations;

use App\Actions\Stripe\RefundDonation;
use App\Enums\DonationStatus;
use App\Models\Donation;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class DonationShow extends Component
{
    public Donation $donation;

    #[Computed]
    public function netAmount(): string
    {
        return $this->donation->currency_symbol.' '.number_format((float) $this->donation->net_amount, 2);
    }

    public function canRefund(): bool
    {
        return $this->donation->status === DonationStatus::Succeeded
            && filled($this->donation->stripe_charge_id);
    }

    public function refund(): void
    {
        if (! $this->canRefund()) {
            $this->dispatch('notify', message: 'This donation cannot be refunded.', variant: 'danger');

            return;
        }

        try {
            app(RefundDonation::class)->handle($this->donation);
            $this->dispatch('notify', message: 'Donation refunded successfully.', variant: 'success');
        } catch (\Throwable $e) {
            report($e);
            $this->dispatch('notify', message: 'Refund failed: '.$e->getMessage(), variant: 'danger');
        }
    }

    public function render()
    {
        return view('livewire.app.donations.show', [
            'title' => 'Donation '.$this->donation->public_id,
        ]);
    }
}
