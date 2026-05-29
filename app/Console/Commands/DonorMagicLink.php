<?php

namespace App\Console\Commands;

use App\Models\Donor;
use App\Models\Organization;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('donor:magic-link {email?} {organization?}')]
#[Description('Generate a magic login link for a donor')]
class DonorMagicLink extends Command
{
    public function handle(): int
    {
        $email = $this->argument('email') ?? $this->ask('Donor email');

        if ($email === null || $email === '') {
            $this->error('Email is required.');

            return self::FAILURE;
        }

        $donor = Donor::query()->where('email', $email)->first();

        if ($donor === null) {
            $this->error("No donor found with email: {$email}");

            return self::FAILURE;
        }

        $orgCode = $this->argument('organization') ?? $this->ask('Organization code');

        if ($orgCode === null || $orgCode === '') {
            $this->error('Organization code is required.');

            return self::FAILURE;
        }

        $organization = Organization::query()->where('code', $orgCode)->first();

        if ($organization === null) {
            $this->error("No organization found with code: {$orgCode}");

            return self::FAILURE;
        }

        $token = $donor->generateMagicToken();
        $url = route('donorportal.magic-login', ['organization' => $organization, 'token' => $token]);

        $this->info("Magic link for {$donor->name} ({$donor->email}):");
        $this->line($url);

        return self::SUCCESS;
    }
}
