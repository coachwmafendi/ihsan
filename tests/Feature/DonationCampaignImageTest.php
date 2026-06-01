<?php

use App\Enums\ElementType;
use App\Models\Campaign;
use App\Models\Element;
use App\Models\Organization;
use Illuminate\Support\Facades\Storage;

it('serves a campaign image for an active donation form token', function () {
    Storage::fake('local');
    Storage::disk('local')->put('campaigns/form-hero.png', 'fake image contents');

    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create([
        'image_path' => 'campaigns/form-hero.png',
    ]);
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'type' => ElementType::Form,
        'is_active' => true,
    ]);

    $response = $this->get(route('donations.campaign-image', $element))
        ->assertOk();

    expect($response->streamedContent())->toBe('fake image contents');
});

it('serves campaign images from local storage even when the default disk is public', function () {
    config(['filesystems.default' => 'public']);

    Storage::fake('local');
    Storage::fake('public');
    Storage::disk('local')->put('campaigns/form-hero.png', 'fake image contents');

    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create([
        'image_path' => 'campaigns/form-hero.png',
    ]);
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'type' => ElementType::Popup,
        'is_active' => true,
    ]);

    $response = $this->get(route('donations.campaign-image', $element))
        ->assertOk();

    expect($response->streamedContent())->toBe('fake image contents');
});

it('does not serve campaign images for inactive forms', function () {
    Storage::fake('local');
    Storage::disk('local')->put('campaigns/form-hero.png', 'fake image contents');

    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create([
        'image_path' => 'campaigns/form-hero.png',
    ]);
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'type' => ElementType::Form,
        'is_active' => false,
    ]);

    $this->get(route('donations.campaign-image', $element))
        ->assertNotFound();
});
