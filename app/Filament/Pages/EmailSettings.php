<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
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
            'sendmail_path' => Setting::get('sendmail_path', config('mail.mailers.sendmail.path')),
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
                    ->required(),
                TextInput::make('mail_from_address')
                    ->label('From Email')
                    ->email()
                    ->required(),
                TextInput::make('mail_from_name')
                    ->label('From Name')
                    ->required(),
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
                    ->visible(fn ($get) => $get('mail_driver') === 'smtp'),
                TextInput::make('sendmail_path')
                    ->label('Sendmail Path')
                    ->default('/usr/sbin/sendmail -bs')
                    ->columnSpan(2)
                    ->visible(fn ($get) => $get('mail_driver') === 'sendmail'),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        Setting::set('mail_driver', $data['mail_driver']);
        Setting::set('mail_from_address', $data['mail_from_address']);
        Setting::set('mail_from_name', $data['mail_from_name']);

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

        if ($data['mail_driver'] === 'sendmail') {
            Setting::set('sendmail_path', $data['sendmail_path']);
        } else {
            Setting::set('sendmail_path', null);
        }

        $this->applyMailConfig($data);

        Notification::make()
            ->title('Email settings saved.')
            ->success()
            ->send();
    }

    public function sendTestEmail(): void
    {
        $data = $this->form->getState();

        if (blank($data['mail_from_address'])) {
            Notification::make()
                ->title('Please save the form first and set a From Email.')
                ->warning()
                ->send();

            return;
        }

        $this->applyMailConfig($data);

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
            'sendmail_path' => Setting::get('sendmail_path'),
        ];

        config([
            'mail.default' => $data['mail_driver'] ?? 'log',
            'mail.from.address' => $data['mail_from_address'] ?? null,
            'mail.from.name' => $data['mail_from_name'] ?? null,
            'mail.mailers.smtp.host' => $data['mail_host'] ?? null,
            'mail.mailers.smtp.port' => $data['mail_port'] ?? null,
            'mail.mailers.smtp.username' => $data['mail_username'] ?? null,
            'mail.mailers.smtp.password' => $data['mail_password'] ?? null,
            'mail.mailers.smtp.encryption' => $data['mail_encryption'] ?? null,
            'mail.mailers.sendmail.path' => $data['sendmail_path'] ?? null,
        ]);
    }
}
