<?php

use Illuminate\Support\Facades\Config;
use Tests\TestCase;

uses(TestCase::class);

it('reads default chip processing fee config', function () {
    expect(config('services.chip.processing_fee_percent'))->toBeFloat()->toBe(2.5);
    expect(config('services.chip.fpx_fee_type'))->toBeString()->toBe('fixed');
    expect(config('services.chip.fpx_fee_amount'))->toBeInt()->toBe(150);
});

it('reads overridden chip processing fee config', function () {
    Config::set('services.chip.processing_fee_percent', 3.5);
    Config::set('services.chip.fpx_fee_type', 'percent');
    Config::set('services.chip.fpx_fee_amount', 200);

    expect(config('services.chip.processing_fee_percent'))->toBeFloat()->toBe(3.5);
    expect(config('services.chip.fpx_fee_type'))->toBeString()->toBe('percent');
    expect(config('services.chip.fpx_fee_amount'))->toBeInt()->toBe(200);
});
