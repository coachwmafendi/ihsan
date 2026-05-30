<?php

namespace App\Actions\Stripe;

use App\Models\Organization;
use Stripe\Account;
use Stripe\AccountLink;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;

class CreateConnectAccount
{
    public function create(Organization $organization): Account
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $account = Account::create([
            'type' => 'standard',
            'email' => $organization->contact_email,
            'business_profile' => [
                'name' => $organization->name,
                'url' => $organization->website_url,
            ],
            'metadata' => [
                'organization_id' => (string) $organization->getKey(),
            ],
        ]);

        $organization->update(['stripe_account_id' => $account->id]);

        return $account;
    }

    public function generateOnboardingLink(Organization $organization): string
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $accountLink = AccountLink::create([
            'account' => $organization->stripe_account_id,
            'refresh_url' => url('/app'),
            'return_url' => url('/app'),
            'type' => 'account_onboarding',
        ]);

        return $accountLink->url;
    }

    public function verifyAndConnect(string $accountId): array
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        try {
            $account = Account::retrieve($accountId);
        } catch (ApiErrorException) {
            return [
                'valid' => false,
                'reason' => 'This account is not connected to our platform. Please use the "Create new Stripe account" button to create a compatible Connect account.',
            ];
        }

        return [
            'valid' => true,
            'charges_enabled' => $account->charges_enabled ?? false,
        ];
    }
}
