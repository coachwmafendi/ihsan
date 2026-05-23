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
            'mail_driver' => Setting::get('mail_driver', 'log'),
            'mail_host' => Setting::get('mail_host'),
            'mail_port' => Setting::get('mail_port'),
            'mail_username' => Setting::get('mail_username'),
            'mail_password' => Setting::get('mail_password'),
            'mail_encryption' => Setting::get('mail_encryption'),
            'mail_from_address' => Setting::get('mail_from_address'),
            'mail_from_name' => Setting::get('mail_from_name', config('app.name')),
            'sendmail_path' => Setting::get('sendmail_path'),
        ]);
    }

    protected function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Select::make('mail_driver')
                    ->label('Mail Driver')
                    ->options([
                        'smtp' => 'SMTP',
                        'mailgun' => 'Mailgun',
                        'ses' => 'Amazon SES',
                        'postmark' => 'Postmark',
                        'sendmail' => 'Sendmail',
                        'log' => 'Log (for testing)',
                    ])
                    ->required(),
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
                Select::make('mail_encryption')
                    ->label('SMTP Encryption')
                    ->options([
                        'tls' => 'TLS',
                        'ssl' => 'SSL',
                        '' => 'None',
                    ])
                    ->visible(fn ($get) => $get('mail_driver') === 'smtp'),
                TextInput::make('sendmail_path')
                    ->label('Sendmail Path')
                    ->default('/usr/sbin/sendmail -bs')
                    ->visible(fn ($get) => $get('mail_driver') === 'sendmail'),
                TextInput::make('mail_from_address')
                    ->label('From Email')
                    ->email()
                    ->required(),
                TextInput::make('mail_from_name')
                    ->label('From Name')
                    ->required(),
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

        $this->applyMailConfig();

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

    protected function applyMailConfig(): void
    {
        config([
            'mail.default' => Setting::get('mail_driver', 'log'),
            'mail.from.address' => Setting::get('mail_from_address'),
            'mail.from.name' => Setting::get('mail_from_name'),
            'mail.mailers.smtp.host' => Setting::get('mail_host'),
            'mail.mailers.smtp.port' => Setting::get('mail_port'),
            'mail.mailers.smtp.username' => Setting::get('mail_username'),
            'mail.mailers.smtp.password' => Setting::get('mail_password'),
            'mail.mailers.smtp.encryption' => Setting::get('mail_encryption'),
            'mail.mailers.sendmail.path' => Setting::get('sendmail_path', '/usr/sbin/sendmail -bs'),
        ]);
    }
}
