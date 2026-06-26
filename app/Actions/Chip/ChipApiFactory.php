<?php

declare(strict_types=1);

namespace App\Actions\Chip;

use App\Models\Organization;
use Chip\ChipApi;
use InvalidArgumentException;

final class ChipApiFactory
{
    public static function make(Organization $organization): ChipApi
    {
        if (! $organization->chip_onboarded) {
            throw new InvalidArgumentException('Organization is not CHIP onboarded.');
        }

        return new ChipApi(
            brandId: $organization->chip_brand_id,
            apiKey: $organization->chip_api_key,
        );
    }
}
