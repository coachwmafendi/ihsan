<?php

declare(strict_types=1);

namespace App\Livewire\App\Donations;

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

    public function render()
    {
        return view('livewire.app.donations.show', [
            'title' => 'Donation '.$this->donation->public_id,
        ]);
    }
}
