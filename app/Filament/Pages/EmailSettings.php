<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;

class EmailSettings extends Page
{
    protected string $view = 'filament.admin.pages.email-settings';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $navigationLabel = 'Email';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 1;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'mail_driver' => Setting::get('mail_driver', config('mail.default', 'log')),
            'mail_host' => Setting::get('mail_host', config('mail.mailers.smtp.host')),
            'mail_port' => Setting::get('mail_port', config('mail.mailers.smtp.port')),
            'mail_username' => Setting::get('mail_username', config('mail.mailers.smtp.username')),
            'mail_password' => Setting::get('mail_password', config('mail.mailers.smtp.password')),
            'mail_encryption' => Setting::get('mail_encryption', env('MAIL_ENCRYPTION')),
            'mail_from_address' => Setting::get('mail_from_address', config('mail.from.address')),
            'mail_from_name' => Setting::get('mail_from_name', config('mail.from.name', config('app.name'))),
            'mail_throttle_seconds' => Setting::get('mail_throttle_seconds', config('mail.throttle_seconds', 0)),
            'sendmail_path' => Setting::get('sendmail_path', config('mail.mailers.sendmail.path')),
            'ses_key' => Setting::get('ses_key', config('services.ses.key')),
            'ses_secret' => Setting::get('ses_secret', config('services.ses.secret')),
            'ses_region' => Setting::get('ses_region', config('services.ses.region', 'us-east-1')),
            'mailgun_domain' => Setting::get('mailgun_domain', config('services.mailgun.domain')),
            'mailgun_secret' => Setting::get('mailgun_secret', config('services.mailgun.secret')),
            'postmark_token' => Setting::get('postmark_token', config('services.postmark.token')),
        ]);
    }

    protected function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->schema([
                Select::make('mail_driver')
                    ->label('Mail Driver')
                    ->options([
                        'smtp' => 'SMTP',
                        'mailgun' => 'Mailgun',
                        'ses' => 'Amazon SES',
                        'postmark' => 'Postmark',
                        'sendmail' => 'Sendmail',
                        'log' => 'Log',
                    ])
                    ->live()
                    ->required(),
                TextInput::make('mail_from_address')
                    ->label('From Email')
                    ->email()
                    ->required(),
                TextInput::make('mail_from_name')
                    ->label('From Name')
                    ->required(),
                TextInput::make('mail_throttle_seconds')
                    ->label('Throttle Seconds')
                    ->helperText('Delay between each email (0 = no throttling)')
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->required(),

                // SMTP
                Select::make('mail_encryption')
                    ->label('SMTP Encryption')
                    ->options([
                        'tls' => 'TLS',
                        'ssl' => 'SSL',
                        '' => 'None',
                    ])
                    ->visible(fn ($get) => $get('mail_driver') === 'smtp'),
                TextInput::make('mail_host')
                    ->label('SMTP Host')
                    ->visible(fn ($get) => $get('mail_driver') === 'smtp'),
                TextInput::make('mail_port')
                    ->label('SMTP Port')
                    ->numeric()
                    ->visible(fn ($get) => $get('mail_driver') === 'smtp'),
                TextInput::make('mail_username')
                    ->label('SMTP Username')
                    ->visible(fn ($get) => $get('mail_driver') === 'smtp'),
                TextInput::make('mail_password')
                    ->label('SMTP Password')
                    ->password()
                    ->revealable()
                    ->visible(fn ($get) => $get('mail_driver') === 'smtp'),

                // Amazon SES
                TextInput::make('ses_key')
                    ->label('AWS Access Key ID')
                    ->visible(fn ($get) => $get('mail_driver') === 'ses'),
                TextInput::make('ses_secret')
                    ->label('AWS Secret Access Key')
                    ->password()
                    ->revealable()
                    ->visible(fn ($get) => $get('mail_driver') === 'ses'),
                TextInput::make('ses_region')
                    ->label('AWS Region')
                    ->default('us-east-1')
                    ->visible(fn ($get) => $get('mail_driver') === 'ses'),

                // Mailgun
                TextInput::make('mailgun_domain')
                    ->label('Mailgun Domain')
                    ->visible(fn ($get) => $get('mail_driver') === 'mailgun'),
                TextInput::make('mailgun_secret')
                    ->label('Mailgun Secret')
                    ->password()
                    ->revealable()
                    ->visible(fn ($get) => $get('mail_driver') === 'mailgun'),

                // Postmark
                TextInput::make('postmark_token')
                    ->label('Postmark Token')
                    ->password()
                    ->revealable()
                    ->visible(fn ($get) => $get('mail_driver') === 'postmark'),

                // Sendmail
                TextInput::make('sendmail_path')
                    ->label('Sendmail Path')
                    ->default('/usr/sbin/sendmail -bs')
                    ->visible(fn ($get) => $get('mail_driver') === 'sendmail'),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $oldSettings = [
            'mail_driver' => Setting::get('mail_driver'),
            'mail_host' => Setting::get('mail_host'),
            'mail_port' => Setting::get('mail_port'),
            'mail_username' => Setting::get('mail_username'),
            'mail_password' => Setting::get('mail_password') ? '***' : null,
            'mail_encryption' => Setting::get('mail_encryption'),
            'mail_from_address' => Setting::get('mail_from_address'),
            'mail_from_name' => Setting::get('mail_from_name'),
            'mail_throttle_seconds' => Setting::get('mail_throttle_seconds', 0),
            'sendmail_path' => Setting::get('sendmail_path'),
            'ses_key' => Setting::get('ses_key'),
            'ses_secret' => Setting::get('ses_secret') ? '***' : null,
            'ses_region' => Setting::get('ses_region'),
            'mailgun_domain' => Setting::get('mailgun_domain'),
            'mailgun_secret' => Setting::get('mailgun_secret') ? '***' : null,
            'postmark_token' => Setting::get('postmark_token') ? '***' : null,
        ];

        Setting::set('mail_driver', $data['mail_driver']);
        Setting::set('mail_from_address', $data['mail_from_address']);
        Setting::set('mail_from_name', $data['mail_from_name']);
        Setting::set('mail_throttle_seconds', $data['mail_throttle_seconds'] ?? 0);

        // SMTP
        if ($data['mail_driver'] === 'smtp') {
            Setting::set('mail_host', $data['mail_host']);
            Setting::set('mail_port', $data['mail_port']);
            Setting::set('mail_username', $data['mail_username']);
            Setting::set('mail_password', $data['mail_password']);
            Setting::set('mail_encryption', $data['mail_encryption'] ?? '');
        } else {
            foreach (['mail_host', 'mail_port', 'mail_username', 'mail_password', 'mail_encryption'] as $key) {
                Setting::set($key, null);
            }
        }

        // Amazon SES
        if ($data['mail_driver'] === 'ses') {
            Setting::set('ses_key', $data['ses_key']);
            Setting::set('ses_secret', $data['ses_secret']);
            Setting::set('ses_region', $data['ses_region']);
        } else {
            foreach (['ses_key', 'ses_secret', 'ses_region'] as $key) {
                Setting::set($key, null);
            }
        }

        // Mailgun
        if ($data['mail_driver'] === 'mailgun') {
            Setting::set('mailgun_domain', $data['mailgun_domain']);
            Setting::set('mailgun_secret', $data['mailgun_secret']);
        } else {
            foreach (['mailgun_domain', 'mailgun_secret'] as $key) {
                Setting::set($key, null);
            }
        }

        // Postmark
        if ($data['mail_driver'] === 'postmark') {
            Setting::set('postmark_token', $data['postmark_token']);
        } else {
            Setting::set('postmark_token', null);
        }

        // Sendmail
        if ($data['mail_driver'] === 'sendmail') {
            Setting::set('sendmail_path', $data['sendmail_path']);
        } else {
            Setting::set('sendmail_path', null);
        }

        $this->applyMailConfig($data);

        activity()
            ->causedBy(auth()->user())
            ->withProperties([
                'old' => $oldSettings,
                'new' => [
                    'mail_driver' => $data['mail_driver'],
                    'mail_host' => $data['mail_host'] ?? null,
                    'mail_port' => $data['mail_port'] ?? null,
                    'mail_username' => $data['mail_username'] ?? null,
                    'mail_password' => filled($data['mail_password'] ?? null) ? '***' : null,
                    'mail_encryption' => $data['mail_encryption'] ?? null,
                    'mail_from_address' => $data['mail_from_address'],
                    'mail_from_name' => $data['mail_from_name'],
                    'mail_throttle_seconds' => $data['mail_throttle_seconds'] ?? 0,
                    'sendmail_path' => $data['sendmail_path'] ?? null,
                    'ses_key' => $data['ses_key'] ?? null,
                    'ses_secret' => filled($data['ses_secret'] ?? null) ? '***' : null,
                    'ses_region' => $data['ses_region'] ?? null,
                    'mailgun_domain' => $data['mailgun_domain'] ?? null,
                    'mailgun_secret' => filled($data['mailgun_secret'] ?? null) ? '***' : null,
                    'postmark_token' => filled($data['postmark_token'] ?? null) ? '***' : null,
                ],
            ])
            ->log('Email settings updated');

        Artisan::call('queue:restart');

        Notification::make()
            ->title('Email settings saved — queue workers restarted.')
            ->success()
            ->send();
    }

    public function sendTestEmail(): void
    {
        $this->save();

        $data = $this->form->getState();

        if (blank($data['mail_from_address'])) {
            Notification::make()
                ->title('Please set a From Email before sending a test.')
                ->warning()
                ->send();

            return;
        }

        $this->applyMailConfig();

        try {
            Mail::raw('This is a test email from '.config('app.name').'. Your email configuration is working correctly.', function ($message) use ($data) {
                $message->to($data['mail_from_address'])
                    ->subject('Test Email — '.config('app.name'));
            });

            Notification::make()
                ->title('Test email sent to '.$data['mail_from_address'])
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Failed to send test email: '.$e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function applyMailConfig(?array $data = null): void
    {
        $data ??= [
            'mail_driver' => Setting::get('mail_driver', 'log'),
            'mail_host' => Setting::get('mail_host'),
            'mail_port' => Setting::get('mail_port'),
            'mail_username' => Setting::get('mail_username'),
            'mail_password' => Setting::get('mail_password'),
            'mail_encryption' => Setting::get('mail_encryption'),
            'mail_from_address' => Setting::get('mail_from_address'),
            'mail_from_name' => Setting::get('mail_from_name'),
            'mail_throttle_seconds' => Setting::get('mail_throttle_seconds', 0),
            'sendmail_path' => Setting::get('sendmail_path'),
            'ses_key' => Setting::get('ses_key'),
            'ses_secret' => Setting::get('ses_secret'),
            'ses_region' => Setting::get('ses_region'),
            'mailgun_domain' => Setting::get('mailgun_domain'),
            'mailgun_secret' => Setting::get('mailgun_secret'),
            'postmark_token' => Setting::get('postmark_token'),
        ];

        config([
            'mail.default' => $data['mail_driver'] ?? 'log',
            'mail.from.address' => $data['mail_from_address'] ?? null,
            'mail.from.name' => $data['mail_from_name'] ?? null,
            'mail.throttle_seconds' => (int) ($data['mail_throttle_seconds'] ?? 0),
            'mail.mailers.smtp.host' => $data['mail_host'] ?? null,
            'mail.mailers.smtp.port' => $data['mail_port'] ?? null,
            'mail.mailers.smtp.username' => $data['mail_username'] ?? null,
            'mail.mailers.smtp.password' => $data['mail_password'] ?? null,
            'mail.mailers.smtp.encryption' => $data['mail_encryption'] ?? null,
            'mail.mailers.sendmail.path' => $data['sendmail_path'] ?? null,
            'services.ses.key' => $data['ses_key'] ?? null,
            'services.ses.secret' => $data['ses_secret'] ?? null,
            'services.ses.region' => $data['ses_region'] ?? null,
            'services.mailgun.domain' => $data['mailgun_domain'] ?? null,
            'services.mailgun.secret' => $data['mailgun_secret'] ?? null,
            'services.postmark.token' => $data['postmark_token'] ?? null,
        ]);
    }
}
