<?php

declare(strict_types=1);

namespace App\Services\GeoLocation;

use GeoIp2\Database\Reader;

class MaxMindGeoIp
{
    public function __construct(
        private string $databasePath,
    ) {}

    /**
     * @return array{geo_city: string|null, geo_region: string|null}|null
     */
    public function lookup(string $ip): ?array
    {
        if (! file_exists($this->databasePath)) {
            return null;
        }

        try {
            $reader = new Reader($this->databasePath);
            $record = $reader->city($ip);

            return [
                'geo_city' => $record->city->name,
                'geo_region' => $record->subdivisions[0]->name ?? null,
            ];
        } catch (\Throwable) {
            return null;
        }
    }
}
