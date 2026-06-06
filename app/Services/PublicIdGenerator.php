<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Element;
use App\Models\MonthlyInvoice;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class PublicIdGenerator
{
    private const CHARSET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ123456789';

    /**
     * Prefix configurations per table/model.
     *
     * @var array<class-string<Model>, array{prefix: string, randomLength: int}>
     */
    private const CONFIG = [
        User::class => ['prefix' => 'U', 'randomLength' => 7],
        Campaign::class => ['prefix' => 'IH', 'randomLength' => 6],
        Donor::class => ['prefix' => 'DR', 'randomLength' => 6],
        Donation::class => ['prefix' => 'D', 'randomLength' => 7],
        Subscription::class => ['prefix' => 'R', 'randomLength' => 7],
        Element::class => ['prefix' => 'E', 'randomLength' => 7],
        MonthlyInvoice::class => ['prefix' => 'I', 'randomLength' => 7],
    ];

    private const MAX_RETRIES = 10;

    public static function generate(string $modelClass): string
    {
        $config = self::CONFIG[$modelClass]
            ?? throw new \InvalidArgumentException("No public_id config for model: {$modelClass}");

        $prefix = $config['prefix'];
        $length = $config['randomLength'];

        for ($attempt = 1; $attempt <= self::MAX_RETRIES; $attempt++) {
            $candidate = $prefix.self::randomString($length);

            if (! $modelClass::where('public_id', $candidate)->exists()) {
                return $candidate;
            }
        }

        throw new \RuntimeException("Failed to generate unique public_id for {$modelClass} after ".self::MAX_RETRIES.' attempts');
    }

    private static function randomString(int $length): string
    {
        $result = '';
        $maxIndex = strlen(self::CHARSET) - 1;

        for ($i = 0; $i < $length; $i++) {
            $result .= self::CHARSET[random_int(0, $maxIndex)];
        }

        return $result;
    }
}
