<?php

use Tests\TestCase;

uses(TestCase::class);

it('reads chip processing fee config', function () {
    expect(config('services.chip.processing_fee_percent'))->toBeFloat();
    expect(config('services.chip.fpx_fee_type'))->toBeIn(['fixed', 'percent']);
});
