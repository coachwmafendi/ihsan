<?php

declare(strict_types=1);

namespace App\Livewire\App\Settings;

use App\Models\Organization;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class Profile extends Component
{
    use WithFileUploads;

    public string $activeTab = 'information';

    // Organization fields
    public string $name = '';

    public ?string $ros_rob_number = null;

    public ?string $description = null;

    public ?string $website_url = null;

    public ?string $contact_email = null;

    public ?string $contact_phone = null;

    public $logo = null; // For file upload

    public ?string $existing_logo = null;

    // Address fields
    public ?string $address_line_1 = null;

    public ?string $address_line_2 = null;

    public ?string $city = null;

    public ?string $state = null;

    public ?string $postcode = null;

    public ?string $country = 'Malaysia';

    // Bank fields
    public ?string $bank_name = null;

    public ?string $bank_account_name = null;

    public ?string $bank_account_number = null;

    // Settings
    public array $allowed_domains = [];

    public ?string $portal_reply_to_email = null;

    public ?string $portal_tagline = null;

    public ?string $portal_receipt_footer = null;

    public ?string $social_facebook = null;

    public ?string $social_instagram = null;

    public ?string $social_twitter = null;

    public ?string $social_tiktok = null;

    public ?string $social_youtube = null;

    public function mount(): void
    {
        /** @var Organization|null $org */
        $org = Auth::user()?->organization;

        if (! $org) {
            return;
        }

        $this->name = $org->name ?? '';
        $this->ros_rob_number = $org->ros_rob_number;
        $this->description = $org->description;
        $this->website_url = $org->website_url;
        $this->contact_email = $org->contact_email;
        $this->contact_phone = $org->contact_phone;
        $this->existing_logo = $org->logo_path;

        $this->address_line_1 = $org->address_line_1;
        $this->address_line_2 = $org->address_line_2;
        $this->city = $org->city;
        $this->state = $org->state;
        $this->postcode = $org->postcode;
        $this->country = $org->country ?? 'Malaysia';

        $this->bank_name = $org->bank_name;
        $this->bank_account_name = $org->bank_account_name;
        $this->bank_account_number = $org->bank_account_number;

        /** @var array<string, mixed> $settings */
        $settings = $org->settings ?? [];
        $this->allowed_domains = $settings['allowed_domains'] ?? [];
        $this->portal_reply_to_email = $settings['portal_reply_to_email'] ?? $org->contact_email;
        $this->portal_tagline = $settings['portal_tagline'] ?? null;
        $this->portal_receipt_footer = $settings['portal_receipt_footer'] ?? null;
        $this->social_facebook = $settings['social_facebook'] ?? null;
        $this->social_instagram = $settings['social_instagram'] ?? null;
        $this->social_twitter = $settings['social_twitter'] ?? null;
        $this->social_tiktok = $settings['social_tiktok'] ?? null;
        $this->social_youtube = $settings['social_youtube'] ?? null;
    }

    public function addDomain(string $domain): void
    {
        $domain = trim($domain);
        if ($domain !== '') {
            $this->allowed_domains[] = $domain;
        }
    }

    public function removeDomain(int $index): void
    {
        array_splice($this->allowed_domains, $index, 1);
        $this->allowed_domains = array_values($this->allowed_domains);
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'website_url' => ['nullable', 'url', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:255'],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'postcode' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:255'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_account_name' => ['nullable', 'string', 'max:255'],
            'bank_account_number' => ['nullable', 'string', 'max:255'],
            'allowed_domains' => ['nullable', 'array'],
            'allowed_domains.*' => ['string', 'max:255'],
        ]);

        /** @var Organization|null $org */
        $org = Auth::user()?->organization;

        if (! $org) {
            return;
        }

        /** @var array<string, mixed> $existingSettings */
        $existingSettings = $org->settings ?? [];

        $settings = array_merge($existingSettings, [
            'allowed_domains' => $this->normalizeDomains($this->allowed_domains),
            'portal_reply_to_email' => $this->portal_reply_to_email,
            'portal_tagline' => $this->portal_tagline,
            'portal_receipt_footer' => $this->portal_receipt_footer,
            'social_facebook' => $this->social_facebook,
            'social_instagram' => $this->social_instagram,
            'social_twitter' => $this->social_twitter,
            'social_tiktok' => $this->social_tiktok,
            'social_youtube' => $this->social_youtube,
        ]);

        $updateData = [
            'name' => $this->name,
            'ros_rob_number' => $this->ros_rob_number,
            'description' => $this->description,
            'website_url' => $this->website_url,
            'contact_email' => $this->contact_email,
            'contact_phone' => $this->contact_phone,
            'address_line_1' => $this->address_line_1,
            'address_line_2' => $this->address_line_2,
            'city' => $this->city,
            'state' => $this->state,
            'postcode' => $this->postcode,
            'country' => $this->country,
            'bank_name' => $this->bank_name,
            'bank_account_name' => $this->bank_account_name,
            'bank_account_number' => $this->bank_account_number,
            'settings' => $settings,
        ];

        if ($this->logo) {
            /** @var UploadedFile $logo */
            $logo = $this->logo;
            $path = $logo->store('organizations/logos', 'public');
            $updateData['logo_path'] = $path;
            $this->existing_logo = $path;
            $this->logo = null;
        }

        $org->update($updateData);

        $this->dispatch('notify', message: 'Organisation profile saved.', variant: 'success');
    }

    /**
     * @param  array<int, string>  $domains
     * @return array<int, string>
     */
    private function normalizeDomains(array $domains): array
    {
        return collect($domains)
            ->filter()
            ->map(function (string $domain): string {
                $domain = trim($domain);
                $host = parse_url($domain, PHP_URL_HOST);

                if ($host) {
                    $domain = $host;
                }

                return ltrim(strtolower($domain), 'www.');
            })
            ->unique()
            ->values()
            ->all();
    }

    public function render()
    {
        return view('livewire.app.settings.profile', ['title' => 'Settings — Profile']);
    }
}
