<?php

namespace App\Filament\App\Resources\Donations\Pages;

use App\Actions\Stripe\RefundDonation;
use App\Enums\DonationStatus;
use App\Filament\App\Resources\Donations\DonationResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewDonation extends ViewRecord
{
    protected static string $resource = DonationResource::class;

    protected string $view = 'filament.app.resources.donations.pages.view-donation';

    public function getHeading(): string
    {
        $currency = strtoupper($this->record->currency ?? 'MYR');
        $amount = number_format((float) $this->record->gross_amount, 2);

        return "{$currency} {$amount} — #{$this->record->public_id}";
    }

    public function hasFormWrapper(): bool
    {
        return false;
    }

    protected function getFormActions(): array
    {
        return [];
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function refundDonation(): void
    {
        if ($this->record->status !== DonationStatus::Succeeded) {
            return;
        }

        try {
            app(RefundDonation::class)->handle($this->record);
            $this->record->refresh();

            Notification::make()
                ->title('Refund successful.')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Refund failed: '.$e->getMessage())
                ->danger()
                ->send();
        }
    }
}
