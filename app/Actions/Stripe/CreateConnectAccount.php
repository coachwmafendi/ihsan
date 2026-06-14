<?php

namespace App\Actions\Stripe;

use App\Models\Organization;
use Stripe\Account;
use Stripe\AccountLink;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;

class CreateConnectAccount
{
    /**
     * @var array<string, string>
     */
    private array $countryCodes = [
        'Malaysia' => 'MY',
        'Brunei' => 'BN',
        'Cambodia' => 'KH',
        'Indonesia' => 'ID',
        'Myanmar' => 'MM',
        'Philippines' => 'PH',
        'Singapore' => 'SG',
        'Thailand' => 'TH',
        'Vietnam' => 'VN',
        'Bangladesh' => 'BD',
        'India' => 'IN',
        'Pakistan' => 'PK',
        'Sri Lanka' => 'LK',
        'Australia' => 'AU',
        'China' => 'CN',
        'Japan' => 'JP',
        'South Korea' => 'KR',
        'Taiwan' => 'TW',
        'United Kingdom' => 'GB',
        'United States' => 'US',
    ];

    public function create(Organization $organization): Account
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $country = $organization->country
            ? ($this->countryCodes[$organization->country] ?? 'MY')
            : 'MY';

        $account = Account::create([
            'type' => 'express',
            'country' => $country,
            'email' => $organization->contact_email,
            'business_profile' => [
                'name' => $organization->name,
                'url' => $organization->website_url,
            ],
            'capabilities' => [
                'card_payments' => ['requested' => true],
                'transfers' => ['requested' => true],
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
            'refresh_url' => route('app.stripe-onboarding', ['onboarding' => 'refresh']),
            'return_url' => route('app.stripe-onboarding', ['onboarding' => 'success']),
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
