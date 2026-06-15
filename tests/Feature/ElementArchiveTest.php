<?php

use App\Models\Element;
use App\Models\Organization;
use App\Models\Campaign;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('archives an element by setting archived_at', function () {
    $org = Organization::factory()->create();
    $campaign = Campaign::factory()->for($org)->create();
    $element = Element::factory()->for($org)->for($campaign)->create(['is_active' => true]);

    expect($element->archived_at)->toBeNull();
    expect($element->isArchived())->toBeFalse();

    $element->archive();

    expect($element->fresh()->archived_at)->not->toBeNull();
    expect($element->fresh()->isArchived())->toBeTrue();
    expect($element->fresh()->is_active)->toBeFalse();
});
