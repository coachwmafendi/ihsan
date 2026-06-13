<?php

use App\Models\Organization;

test('organizations table has facebook_url column', function () {
    expect(Schema::hasColumn('organizations', 'facebook_url'))->toBeTrue();
});
