<?php

declare(strict_types=1);

namespace App\Actions\Chip;

final class PaymentMethodWhitelistMapper
{
    /**
     * @var array<string, string[]>
     */
    private const CATEGORY_METHODS = [
        'card' => ['visa', 'mastercard', 'maestro', 'mpgs_apple_pay', 'mpgs_google_pay'],
        'fpx' => ['fpx'],
    ];

    /**
     * Map user-facing payment-method categories to CHIP SDK payment_method_whitelist
     * enum values.
     *
     * @param  string[]  $categories
     * @return string[]
     */
    public static function map(array $categories): array
    {
        $methods = [];

        foreach ($categories as $category) {
            if (isset(self::CATEGORY_METHODS[$category])) {
                $methods = array_merge($methods, self::CATEGORY_METHODS[$category]);
            }
        }

        return array_values(array_unique($methods));
    }

    /**
     * @return string[]
     */
    public static function cardOnly(): array
    {
        return self::map(['card']);
    }
}
