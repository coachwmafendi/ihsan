<?php

namespace App\Jobs;

use App\Enums\UserRole;
use App\Models\User;
use App\Notifications\AdminToOrgAdminNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendAdminNotificationToOrgs implements ShouldQueue
{
    use Queueable;

    /**
     * @param  list<int>  $organizationIds
     */
    public function __construct(
        public string $message,
        public string $senderName,
        public array $organizationIds,
        public string $type,
        public ?string $image,
    ) {}

    public function handle(): void
    {
        foreach ($this->organizationIds as $organizationId) {
            $orgAdmins = User::query()
                ->where('organization_id', $organizationId)
                ->where('role', UserRole::NgoAdmin)
                ->get();

            foreach ($orgAdmins as $admin) {
                $admin->notify(new AdminToOrgAdminNotification(
                    message: $this->message,
                    senderName: $this->senderName,
                    organizationId: $organizationId,
                    type: $this->type,
                    image: $this->image,
                ));
            }
        }
    }
}
