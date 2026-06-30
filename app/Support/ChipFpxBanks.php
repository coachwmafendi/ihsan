<?php

declare(strict_types=1);

namespace App\Support;

final class ChipFpxBanks
{
    /**
     * FPX B2C bank list from CHIP documentation.
     *
     * @see https://docs.chip-in.asia/chip-collect/overview/direct-post/fpx
     *
     * @return array<int, array{name: string, code: string}>
     */
    public static function b2c(): array
    {
        return [
            ['name' => 'Affin Bank', 'code' => 'ABB0233'],
            ['name' => 'Alliance Bank (Personal)', 'code' => 'ABMB0212'],
            ['name' => 'AGRONet', 'code' => 'AGRO01'],
            ['name' => 'AmBank', 'code' => 'AMBB0209'],
            ['name' => 'Bank Islam', 'code' => 'BIMB0340'],
            ['name' => 'Bank Muamalat', 'code' => 'BMMB0341'],
            ['name' => 'Bank Rakyat', 'code' => 'BKRM0602'],
            ['name' => 'Bank Of China', 'code' => 'BOCM01'],
            ['name' => 'BSN', 'code' => 'BSN0601'],
            ['name' => 'CIMB Bank', 'code' => 'BCBB0235'],
            ['name' => 'Hong Leong Bank', 'code' => 'HLB0224'],
            ['name' => 'HSBC Bank', 'code' => 'HSBC0223'],
            ['name' => 'KFH', 'code' => 'KFH0346'],
            ['name' => 'Maybank2E', 'code' => 'MBB0228'],
            ['name' => 'Maybank2u', 'code' => 'MB2U0227'],
            ['name' => 'MBSB Bank', 'code' => 'MBSB001'],
            ['name' => 'OCBC Bank', 'code' => 'OCBC0229'],
            ['name' => 'Public Bank', 'code' => 'PBB0233'],
            ['name' => 'RHB Bank', 'code' => 'RHB0218'],
            ['name' => 'Standard Chartered', 'code' => 'SCB0216'],
            ['name' => 'UOB Bank', 'code' => 'UOB0226'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function b2cMap(): array
    {
        return collect(self::b2c())->mapWithKeys(fn (array $bank): array => [$bank['code'] => $bank['name']])->all();
    }

    public static function isValidB2cCode(string $code): bool
    {
        return array_key_exists($code, self::b2cMap());
    }
}
