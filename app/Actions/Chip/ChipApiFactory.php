<?php

declare(strict_types=1);

namespace App\Actions\Chip;

use App\Models\Organization;
use Chip\ChipApi;
use Closure;
use InvalidArgumentException;

final class ChipApiFactory
{
    /** @var Closure(Organization): ChipApi|null */
    private static ?Closure $fakeMake = null;

    private static ?string $fakePublicKey = null;

    private static ?bool $fakeVerifyWebhook = null;

    /**
     * @param  Closure(Organization): ChipApi|null  $make
     */
    public static function fake(
        ?Closure $make = null,
        ?string $publicKey = null,
        ?bool $verifyWebhook = null,
    ): void {
        self::$fakeMake = $make;
        self::$fakePublicKey = $publicKey;
        self::$fakeVerifyWebhook = $verifyWebhook;
    }

    public static function resetFake(): void
    {
        self::$fakeMake = null;
        self::$fakePublicKey = null;
        self::$fakeVerifyWebhook = null;
    }

    public static function make(Organization $organization): ChipApi
    {
        if (self::$fakeMake !== null) {
            return (self::$fakeMake)($organization);
        }

        if (! $organization->chip_active) {
            throw new InvalidArgumentException('Organization is not CHIP onboarded.');
        }

        return new ChipApi(
            $organization->chip_brand_id,
            $organization->chip_api_key,
        );
    }

    public static function getPublicKey(Organization $organization): string
    {
        if (self::$fakePublicKey !== null) {
            return self::$fakePublicKey;
        }

        if (! $organization->chip_active) {
            throw new InvalidArgumentException('Organization is not CHIP onboarded.');
        }

        return cache()->remember(
            "chip_public_key:{$organization->chip_brand_id}",
            3600,
            function () use ($organization): string {
                $key = self::make($organization)->publicKey->get()->key ?? null;

                if (! is_string($key) || blank($key)) {
                    throw new \RuntimeException('Unable to fetch CHIP public key.');
                }

                return $key;
            }
        );
    }

    public static function verifyWebhook(string $payload, string $signature, string $publicKey): bool
    {
        if (self::$fakeVerifyWebhook !== null) {
            return self::$fakeVerifyWebhook;
        }

        return ChipApi::verify($payload, $signature, $publicKey);
    }
}
