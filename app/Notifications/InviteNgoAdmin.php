<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Password;

class InviteNgoAdmin extends Notification
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

        return (new MailMessage)
            ->subject('Anda telah dijemput — '.config('app.name'))
            ->greeting('Assalamualaikum '.$notifiable->name.',')
            ->line('Anda telah dijemput sebagai pentadbir untuk **'.$this->organizationName.'** di platform '.config('app.name').'.')
            ->line('Sila tetapkan kata laluan anda melalui butang di bawah untuk mula menggunakan panel.')
            ->action('Tetapkan Kata Laluan', url(route('password.reset', ['token' => $token, 'email' => $notifiable->getEmailForPasswordReset()], false)))
            ->line('Pautan ini akan tamat dalam '.config('auth.passwords.'.config('auth.defaults.passwords').'.expire').' minit.')
            ->line('Jika anda tidak menjangka jemputan ini, sila abaikan email ini.');
    }
}
