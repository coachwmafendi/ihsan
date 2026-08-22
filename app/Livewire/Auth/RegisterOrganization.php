<?php

namespace App\Livewire\Auth;

use App\Enums\OrganizationStatus;
use App\Mail\NewOrganizationRegistered;
use App\Models\Organization;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Throwable;

class RegisterOrganization extends Component
{
    #[Rule(['required', 'string', 'max:255'])]
    public string $name = '';

    #[Rule(['required', 'in:ROS,ROB,Others'])]
    public string $registration_type = '';

    #[Rule(['required', 'string', 'max:100', 'unique:organizations'])]
    public string $ros_rob_number = '';

    #[Rule(['required', 'string', 'max:100'])]
    public string $sector = '';

    #[Rule(['required', 'email', 'max:255'])]
    public string $contact_email = '';

    #[Rule(['required', 'url', 'max:255'])]
    public string $website_url = '';

    #[Rule(['nullable', 'url', 'max:255'])]
    public string $facebook_url = '';

    public bool $submitted = false;

    public function submit(): void
    {
        $this->validate();

        $org = Organization::create([
            'name' => $this->name,
            'registration_type' => $this->registration_type,
            'ros_rob_number' => $this->ros_rob_number,
            'sector' => $this->sector,
            'contact_email' => $this->contact_email,
            'website_url' => $this->website_url,
            'facebook_url' => $this->facebook_url ?: null,
            'status' => OrganizationStatus::Pending,
            'fee_collection_method' => config('services.billing.default_fee_collection_method'),
        ]);

        $host = parse_url($this->website_url, PHP_URL_HOST);
        if (is_string($host) && $host !== '') {
            $host = strtolower($host);
            $normalizedHost = str_starts_with($host, 'www.') ? substr($host, 4) : $host;
            $org->update([
                'settings' => array_merge($org->settings ?? [], [
                    'allowed_domains' => [$normalizedHost],
                ]),
            ]);
        }

        $this->notifyPlatformAdmin($org);

        $this->submitted = true;
    }

    /**
     * Tell the platform admin an organization is waiting for approval.
     *
     * Nothing else surfaces a pending registration, so without this the only
     * way to notice one is to open the admin panel and look. A mail failure
     * must not cost the applicant their registration, which is already saved.
     */
    private function notifyPlatformAdmin(Organization $organization): void
    {
        $adminEmail = config('app.admin_email');

        if (blank($adminEmail)) {
            return;
        }

        try {
            Mail::to($adminEmail)->queue(new NewOrganizationRegistered($organization));
        } catch (Throwable $e) {
            report($e);
        }
    }

    public function render()
    {
        return view('livewire.auth.register-organization')
            ->layout('layouts.landing');
    }
}
