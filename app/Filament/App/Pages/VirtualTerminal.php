<?php

namespace App\Filament\App\Pages;

use App\Actions\Stripe\ProcessVirtualTerminalDonation;
use App\Actions\Stripe\ProcessVirtualTerminalSubscription;
use App\Models\Campaign;
use App\Models\Donor;
use App\Models\Organization;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Validator;

class VirtualTerminal extends Page
{
    protected string $view = 'filament.app.pages.virtual-terminal';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'Virtual Terminal';

    protected static ?string $title = 'Virtual Terminal';

    protected static ?int $navigationSort = 10;

    public ?string $preloadedSupporterPublicId = null;

    public ?Donor $preloadedSupporter = null;

    public ?Organization $organization = null;

    /** @var array<string, mixed> */
    public array $formData = [
        'campaign_id' => null,
        'frequency' => 'once',
        'amount' => '',
        'first_name' => '',
        'last_name' => '',
        'email' => '',
        'payment_method' => 'new_card',
    ];

    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->organization_id !== null;
    }

    public function mount(): void
    {
        $this->organization = auth()->user()->organization;
        $this->preloadedSupporterPublicId = request()->query('vt-supporter');

        if ($this->preloadedSupporterPublicId) {
            $this->loadPreloadedSupporter();
        }

        $this->loadDefaultCampaign();
    }

    public function loadPreloadedSupporter(): void
    {
        $this->preloadedSupporter = Donor::query()
            ->where('public_id', $this->preloadedSupporterPublicId)
            ->first();

        if ($this->preloadedSupporter) {
            $names = explode(' ', $this->preloadedSupporter->name, 2);
            $this->formData['first_name'] = $names[0] ?? '';
            $this->formData['last_name'] = $names[1] ?? '';
            $this->formData['email'] = $this->preloadedSupporter->email;
        }
    }

    public function loadDefaultCampaign(): void
    {
        $defaultCampaign = Campaign::query()
            ->where('organization_id', $this->organization->getKey())
            ->where('status', 'active')
            ->latest()
            ->first();

        if ($defaultCampaign) {
            $this->formData['campaign_id'] = (string) $defaultCampaign->getKey();
        }
    }

    public function clearPreloadedSupporter(): void
    {
        $this->preloadedSupporter = null;
        $this->preloadedSupporterPublicId = null;
        $this->formData['first_name'] = '';
        $this->formData['last_name'] = '';
        $this->formData['email'] = '';
    }

    public function getCampaigns(): array
    {
        return Campaign::query()
            ->where('organization_id', $this->organization->getKey())
            ->where('status', 'active')
            ->pluck('title', 'id')
            ->toArray();
    }

    public function getProcessingFeeEstimate(): string
    {
        $amount = (float) $this->formData['amount'];
        if ($amount <= 0) {
            return 'MYR 0.00';
        }

        $feePercent = (float) config('services.stripe.processing_fee_percent', 2.5);
        $fee = $amount * $feePercent / 100;

        return 'MYR '.number_format($fee, 2);
    }

    public function getTotalAmount(): string
    {
        $amount = (float) $this->formData['amount'];

        return 'MYR '.number_format($amount, 2);
    }

    public function processDonationAction(): Action
    {
        return Action::make('processDonation')
            ->label('Make a donation')
            ->color('gray')
            ->action(function () {
                $data = $this->formData;
                $campaignId = (int) $data['campaign_id'];
                $validator = Validator::make($data, [
                    'campaign_id' => ['required', "exists:campaigns,id,organization_id,{$this->organization->getKey()}"],
                    'frequency' => ['required', 'in:once,monthly'],
                    'amount' => ['required', 'numeric', 'min:1'],
                    'first_name' => ['required', 'string', 'max:255'],
                    'last_name' => ['required', 'string', 'max:255'],
                    'email' => ['required', 'email', 'max:255'],
                ]);

                if ($validator->fails()) {
                    Notification::make()
                        ->title('Please check your form.')
                        ->danger()
                        ->send();

                    return;
                }

                $data = $validator->validated();
                $formattedAmount = number_format((float) $data['amount'], 2);

                try {
                    if ($data['frequency'] === 'once') {
                        app(ProcessVirtualTerminalDonation::class)->handle(
                            campaignId: (int) $data['campaign_id'],
                            amount: (float) $data['amount'],
                            firstName: $data['first_name'],
                            lastName: $data['last_name'],
                            email: $data['email'],
                            organization: $this->organization,
                        );

                        Notification::make()
                            ->title("Donation of MYR {$formattedAmount} processed successfully.")
                            ->success()
                            ->send();
                    } else {
                        app(ProcessVirtualTerminalSubscription::class)->handle(
                            campaignId: (int) $data['campaign_id'],
                            amount: (float) $data['amount'],
                            firstName: $data['first_name'],
                            lastName: $data['last_name'],
                            email: $data['email'],
                            organization: $this->organization,
                        );

                        Notification::make()
                            ->title("Monthly donation of MYR {$formattedAmount} set up successfully.")
                            ->success()
                            ->send();
                    }

                    $this->resetForm();
                } catch (\Throwable $e) {
                    report($e);

                    Notification::make()
                        ->title('Payment failed. Please try again.')
                        ->danger()
                        ->send();
                }
            });
    }

    public function resetForm(): void
    {
        $this->formData = [
            'campaign_id' => $this->formData['campaign_id'],
            'frequency' => 'once',
            'amount' => '',
            'first_name' => $this->preloadedSupporter ? $this->formData['first_name'] : '',
            'last_name' => $this->preloadedSupporter ? $this->formData['last_name'] : '',
            'email' => $this->preloadedSupporter ? $this->formData['email'] : '',
            'payment_method' => 'new_card',
        ];
    }
}
