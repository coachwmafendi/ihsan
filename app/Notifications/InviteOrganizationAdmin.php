<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Password;

class InviteOrganizationAdmin extends Notification
{
    use Queueable;

    public function __construct(
        public string $organizationName,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $token = Password::broker()->createToken($notifiable);

        $appPanelRoot = ($domain = config('app.app_panel_domain'))
            ? 'https://'.$domain
            : config('app.url');

        return (new MailMessage)
            ->subject('You have been invited — '.config('app.name'))
            ->greeting('Hi '.$notifiable->email.',')
            ->line('Your organization ('.$this->organizationName.') registration application has been approved.')
            ->line('You have been invited as an admin for **'.$this->organizationName.'** on the '.config('app.name').' platform.')
            ->line('Please set your password using the button below to start using the panel.')
            ->action('Set Password', $appPanelRoot.route('password.reset', ['token' => $token, 'email' => $notifiable->getEmailForPasswordReset()], false))
            ->line('This link will expire in '.config('auth.passwords.'.config('auth.defaults.passwords').'.expire').' minutes.')
            ->line('If you did not expect this invitation, please ignore this email.');
    }
}
