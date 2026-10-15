<?php

declare(strict_types=1);

use App\Models\Donor;
use App\Models\Organization;
use Illuminate\Support\Facades\File;

/**
 * A bare <select> keeps the browser's own arrow, which sits hard against the
 * right edge inside the field's padding - the year, status, type and every
 * profile dropdown in the portal looked cramped. They all go through
 * x-ui.native-select, which leaves the arrow room.
 */
it('draws every donor portal dropdown with room for its arrow', function () {
    $bare = collect(File::allFiles(resource_path('views/donor')))
        ->filter(fn ($file) => str_contains(File::get($file->getPathname()), '<select'))
        ->map(fn ($file) => str_replace(base_path().'/', '', $file->getPathname()))
        ->values()
        ->all();

    expect($bare)->toBe([]);
});

it('keeps the donor\'s saved choices in the profile dropdowns', function () {
    $organization = Organization::factory()->create();
    $donor = Donor::factory()->create(['title' => 'Dr', 'locale' => 'ms']);

    $html = $this->withSession(['donor_id' => $donor->getKey(), 'organization_id' => $organization->getKey()])
        ->get(route('donorportal.profile', $organization))
        ->assertOk()
        ->getContent();

    expect($html)
        ->toContain('appearance-none pr-10')
        ->toMatch('/<option value="Dr"\s+selected/')
        ->toMatch('/<option value="ms"\s+selected/');
});
