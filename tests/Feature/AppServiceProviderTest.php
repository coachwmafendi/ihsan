<?php

use Illuminate\Support\Facades\URL;

it('uses the configured app url as the url root in console context', function (): void {
    expect(URL::to('/'))->toBe(rtrim(config('app.url'), '/'));
});
