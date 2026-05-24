<?php

namespace App\Filament\App\Pages;

use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Stripe\Account as StripeAccount;
use Stripe\Stripe;

class Settings extends Page implements HasActions
{
    use InteractsWithActions;

    protected string $view = 'filament.app.pages.settings';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Settings';

    protected static ?int $navigationSort = 999;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public bool $notifyNewDonation = false;

    public bool $dailyDonationSummary = false;

    public string $dailySummaryTime = '08:00';

    public bool $failedPaymentNotification = false;

    public bool $notifyNewSubscription = false;

    public bool $notifySubscriptionCancelled = false;

    public bool $notifyLargeDonation = false;

    public int $largeDonationThreshold = 1000;

    public bool $notifyRefund = false;

    public bool $notifyCampaignMilestone = false;

    public bool $monthlyReport = false;

    public function mount(): void
    {
        $org = auth()->user()->organization;
        $settings = $org?->settings ?? [];

        $this->notifyNewDonation = (bool) ($settings['notify_new_donation'] ?? true);
        $this->dailyDonationSummary = (bool) ($settings['daily_donation_summary'] ?? false);
        $this->dailySummaryTime = $settings['daily_summary_time'] ?? '08:00';
        $this->failedPaymentNotification = (bool) ($settings['failed_payment_notification'] ?? true);
        $this->notifyNewSubscription = (bool) ($settings['notify_new_subscription'] ?? true);
        $this->notifySubscriptionCancelled = (bool) ($settings['notify_subscription_cancelled'] ?? true);
        $this->notifyLargeDonation = (bool) ($settings['notify_large_donation'] ?? false);
        $this->largeDonationThreshold = (int) ($settings['large_donation_threshold'] ?? 1000);
        $this->notifyRefund = (bool) ($settings['notify_refund'] ?? true);
        $this->notifyCampaignMilestone = (bool) ($settings['notify_campaign_milestone'] ?? false);
        $this->monthlyReport = (bool) ($settings['monthly_report'] ?? false);

        if ($org !== null) {
            $this->form->fill(array_merge($org->toArray(), [
                'settings' => $settings,
            ]));
        }
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Tabs::make()
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make('Maklumat')
                            ->icon('heroicon-o-building-office')
                            ->columns(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Nama organisasi')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('ros_rob_number')
                                    ->label('Nombor ROS/ROB')
                                    ->nullable()
                                    ->maxLength(255),
                                RichEditor::make('description')
                                    ->label('Deskripsi')
                                    ->nullable()
                                    ->columnSpanFull(),
                            ]),

                        Tab::make('Hubungi')
                            ->icon('heroicon-o-envelope')
                            ->columns(2)
                            ->schema([
                                TextInput::make('website_url')
                                    ->label('URL laman web')
                                    ->url()
                                    ->nullable()
                                    ->maxLength(255)
                                    ->placeholder('https://example.com'),
                                TextInput::make('contact_email')
                                    ->label('E-mel hubungi')
                                    ->email()
                                    ->nullable()
                                    ->maxLength(255),
                                TextInput::make('contact_phone')
                                    ->label('Telefon hubungi')
                                    ->nullable()
                                    ->maxLength(255)
                                    ->prefix('+60')
                                    ->placeholder('123456789'),
                                FileUpload::make('logo_path')
                                    ->label('Logo')
                                    ->image()
                                    ->avatar()
                                    ->imageEditor()
                                    ->directory('organizations/logos')
                                    ->maxSize(2048)
                                    ->imageResizeMode('cover')
                                    ->imageCropAspectRatio('1:1')
                                    ->imageResizeTargetWidth('256')
                                    ->imageResizeTargetHeight('256'),
                            ]),

                        Tab::make('Alamat')
                            ->icon('heroicon-o-map-pin')
                            ->columns(2)
                            ->schema([
                                TextInput::make('address_line_1')
                                    ->label('Alamat 1')
                                    ->nullable()
                                    ->maxLength(255),
                                TextInput::make('address_line_2')
                                    ->label('Alamat 2')
                                    ->nullable()
                                    ->maxLength(255),
                                TextInput::make('city')
                                    ->label('Bandar')
                                    ->nullable()
                                    ->maxLength(255),
                                TextInput::make('state')
                                    ->label('Negeri')
                                    ->nullable()
                                    ->maxLength(255),
                                TextInput::make('postcode')
                                    ->label('Poskod')
                                    ->nullable()
                                    ->maxLength(20),
                                TextInput::make('country')
                                    ->label('Negara')
                                    ->nullable()
                                    ->default('Malaysia')
                                    ->maxLength(100),
                            ]),

                        Tab::make('Sosial')
                            ->icon('heroicon-o-share')
                            ->columns(2)
                            ->schema([
                                TextInput::make('settings.social_facebook')
                                    ->label('Facebook')
                                    ->nullable()
                                    ->maxLength(255)
                                    ->prefix('facebook.com/')
                                    ->placeholder('username'),
                                TextInput::make('settings.social_instagram')
                                    ->label('Instagram')
                                    ->nullable()
                                    ->maxLength(255)
                                    ->prefix('instagram.com/')
                                    ->placeholder('username'),
                                TextInput::make('settings.social_twitter')
                                    ->label('X (Twitter)')
                                    ->nullable()
                                    ->maxLength(255)
                                    ->prefix('x.com/')
                                    ->placeholder('username'),
                                TextInput::make('settings.social_tiktok')
                                    ->label('TikTok')
                                    ->nullable()
                                    ->maxLength(255)
                                    ->prefix('tiktok.com/@')
                                    ->placeholder('username'),
                                TextInput::make('settings.social_youtube')
                                    ->label('YouTube')
                                    ->nullable()
                                    ->maxLength(255)
                                    ->prefix('youtube.com/@')
                                    ->placeholder('channel'),
                            ]),
                    ]),
            ]);
    }

    public function saveProfile(): void
    {
        $data = $this->form->getState();

        $org = auth()->user()->organization;

        if ($org === null) {
            return;
        }

        $settings = array_merge($org->settings ?? [], $data['settings'] ?? []);

        $org->update(array_merge(
            collect($data)->except('settings')->toArray(),
            ['settings' => $settings],
        ));

        Notification::make()
            ->title('Profil organisasi disimpan')
            ->success()
            ->send();
    }

    public function updated(string $property): void
    {
        if (! str_starts_with($property, 'notify') && ! str_starts_with($property, 'daily') && ! str_starts_with($property, 'failed') && ! str_starts_with($property, 'large') && ! str_starts_with($property, 'monthly')) {
            return;
        }

        $this->saveNotificationSettings();
    }

    public function saveNotificationSettings(): void
    {
        $org = auth()->user()->organization;

        if ($org === null) {
            return;
        }

        $settings = array_merge($org->settings ?? [], [
            'notify_new_donation' => $this->notifyNewDonation,
            'daily_donation_summary' => $this->dailyDonationSummary,
            'daily_summary_time' => $this->dailySummaryTime,
            'failed_payment_notification' => $this->failedPaymentNotification,
            'notify_new_subscription' => $this->notifyNewSubscription,
            'notify_subscription_cancelled' => $this->notifySubscriptionCancelled,
            'notify_large_donation' => $this->notifyLargeDonation,
            'large_donation_threshold' => $this->largeDonationThreshold,
            'notify_refund' => $this->notifyRefund,
            'notify_campaign_milestone' => $this->notifyCampaignMilestone,
            'monthly_report' => $this->monthlyReport,
        ]);

        $org->update(['settings' => $settings]);
    }

    public function stripeAccount(): ?StripeAccount
    {
        $org = auth()->user()->organization;

        if ($org === null || $org->stripe_account_id === null) {
            return null;
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        return StripeAccount::retrieve($org->stripe_account_id);
    }

    public function reconnectAction(): Action
    {
        return Action::make('reconnect')
            ->label('Sambung Semula')
            ->icon('heroicon-o-arrow-path')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Sambung Semula Stripe?')
            ->modalDescription('Tindakan ini akan memutuskan sambungan Stripe semasa. Anda perlu menyambung semula akaun Stripe untuk terus menggunakan panel.')
            ->modalSubmitActionLabel('Ya, sambung semula')
            ->action(function () {
                $org = auth()->user()->organization;

                if ($org === null) {
                    return;
                }

                $org->update([
                    'stripe_account_id' => null,
                    'stripe_onboarded' => false,
                ]);

                Notification::make()
                    ->title('Sambungan Stripe telah direset')
                    ->warning()
                    ->send();

                $this->redirect(route('filament.app.pages.stripe-onboarding'), navigate: true);
            });
    }
}
