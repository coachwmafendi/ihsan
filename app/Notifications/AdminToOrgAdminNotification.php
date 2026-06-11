<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AdminToOrgAdminNotification extends Notification
{
    use Queueable;

    public string $message;

    public string $senderName;

    public ?int $organizationId;

    public string $type = 'info'; // info, warning, success, error

    public ?string $image = null;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $message, string $senderName, ?int $organizationId = null, string $type = 'info', ?string $image = null)
    {
        $this->message = $message;
        $this->senderName = $senderName;
        $this->organizationId = $organizationId;
        $this->type = $type;
        $this->image = $image;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message' => $this->message,
            'sender_name' => $this->senderName,
            'organization_id' => $this->organizationId,
            'type' => $this->type,
            'image' => $this->image,
            'timestamp' => now()->toISOString(),
        ];
    }
}
