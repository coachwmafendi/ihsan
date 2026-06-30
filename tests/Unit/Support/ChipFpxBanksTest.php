<?php

declare(strict_types=1);

use App\Support\ChipFpxBanks;

it('returns the fpx b2c bank list', function () {
    $banks = ChipFpxBanks::b2c();

    expect($banks)->toBeArray()
        ->and($banks)->toHaveCount(21)
        ->and($banks[0])->toBe(['name' => 'Affin Bank', 'code' => 'ABB0233'])
        ->and(collect($banks)->pluck('code'))->toContain('MB2U0227')
        ->and(collect($banks)->pluck('name'))->toContain('Maybank2u');
});

it('returns a code to name map', function () {
    $map = ChipFpxBanks::b2cMap();

    expect($map)->toBeArray()
        ->and($map['MB2U0227'])->toBe('Maybank2u')
        ->and($map['BCBB0235'])->toBe('CIMB Bank');
});

it('validates b2c bank codes', function () {
    expect(ChipFpxBanks::isValidB2cCode('MB2U0227'))->toBeTrue()
        ->and(ChipFpxBanks::isValidB2cCode('BCBB0235'))->toBeTrue()
        ->and(ChipFpxBanks::isValidB2cCode('INVALID'))->toBeFalse()
        ->and(ChipFpxBanks::isValidB2cCode(''))->toBeFalse();
});
