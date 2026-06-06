<?php

use App\Services\PublicIdGenerator;

describe('PublicIdGenerator', function () {
    it('throws for unsupported model', function () {
        PublicIdGenerator::generate(stdClass::class);
    })->throws(InvalidArgumentException::class);

    it('has max retries constant of 10', function () {
        $reflection = new ReflectionClass(PublicIdGenerator::class);
        $constant = $reflection->getConstant('MAX_RETRIES');

        expect($constant)->toBe(10);
    });
});
