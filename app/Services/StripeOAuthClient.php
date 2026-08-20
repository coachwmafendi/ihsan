<?php

namespace App\Services;

use Stripe\OAuth;
use Stripe\StripeObject;

/**
 * Thin wrapper around Stripe's static OAuth helpers so callers can depend on an
 * injectable instance instead of the static class.
 */
class StripeOAuthClient
{
    /**
     * @param  array<string, mixed>  $parameters
     */
    public function authorizeUrl(array $parameters): string
    {
        return OAuth::authorizeUrl($parameters);
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    public function token(array $parameters): StripeObject
    {
        return OAuth::token($parameters);
    }
}
