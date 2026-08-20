<?php

namespace App\Jobs;

use App\Enums\UserRole;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendDonorProblemReport implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Donor $donor,
        public Organization $organization,
        public string $message,
    ) {}

    public function handle(): void
    {
        if ($this->organization->trashed()) {
            return;
        }

        $admins = User::query()
            ->where('organization_id', $this->organization->getKey())
            ->where('role', UserRole::NgoAdmin)
            ->get();

        foreach ($admins as $admin) {
            Mail::raw(
                "A donor has reported a problem:\n\n".$this->message."\n\n---\nDonor: {$this->donor->name} ({$this->donor->email})\nOrganization: {$this->organization->name}",
                function ($message) use ($admin): void {
                    $message->to($admin->email)
                        ->subject('Report a Problem — '.$this->organization->name)
                        ->replyTo($this->donor->email);
                }
            );
        }
    }
}
