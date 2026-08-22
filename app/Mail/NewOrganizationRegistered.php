<?php

namespace App\Mail;

use App\Models\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;

class NewOrganizationRegistered extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Organization $organization,
    ) {}

    public function build(): self
    {
        return $this->subject('New organization awaiting approval — '.$this->organization->name)
            ->markdown('emails.new-organization-registered', [
                'organization' => $this->organization,
                'url' => route('filament.admin.resources.organizations.edit', [
                    'record' => $this->organization->getKey(),
                ]),
            ]);
    }
}
