<?php

use App\Models\Campaign;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('generates a slug from the campaign title', function () {
    $organization = Organization::factory()->create();

    $campaign = Campaign::factory()->for($organization)->create([
        'title' => 'Dana Operasi Maahad',
        'slug' => null,
    ]);

    expect($campaign->fresh()->slug)->toBe('dana-operasi-maahad');
});

it('appends a numeric suffix when the slug already exists', function () {
    $organization = Organization::factory()->create();

    Campaign::factory()->for($organization)->create([
        'title' => 'Dana Operasi Maahad',
        'slug' => null,
    ]);

    $duplicate = Campaign::factory()->for($organization)->create([
        'title' => 'Dana Operasi Maahad',
        'slug' => null,
    ]);

    expect($duplicate->fresh()->slug)->toBe('dana-operasi-maahad-1');
});

it('does not override a provided slug', function () {
    $organization = Organization::factory()->create();

    $campaign = Campaign::factory()->for($organization)->create([
        'title' => 'Dana Operasi Maahad',
        'slug' => 'custom-slug',
    ]);

    expect($campaign->fresh()->slug)->toBe('custom-slug');
});
