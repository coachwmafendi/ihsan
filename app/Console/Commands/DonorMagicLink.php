<?php

namespace App\Console\Commands;

use App\Models\Donor;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('donor:magic-link {email?}')]
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

        $token = $donor->generateMagicToken();
        $url = url("/donorportal/login/{$token}");

        $this->info("Magic link for {$donor->name} ({$donor->email}):");
        $this->line($url);

        return self::SUCCESS;
    }
}
