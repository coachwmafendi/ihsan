<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Livewire\App\Campaigns\CampaignCreate;
use App\Models\Organization;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->user = User::factory()->for($this->organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);
});

it('toggles campaign settings on and off', function () {
    $this->actingAs($this->user);

    Livewire::test(CampaignCreate::class)
        ->assertSet('has_target', false)
        ->assertSet('has_end_date', false)
        ->assertSet('allow_recurring', true)
        ->assertSet('allow_custom_amount', true)
        ->call('toggleHasTarget')
        ->assertSet('has_target', true)
        ->call('toggleHasEndDate')
        ->assertSet('has_end_date', true)
        ->call('toggleAllowRecurring')
        ->assertSet('allow_recurring', false)
        ->call('toggleAllowCustomAmount')
        ->assertSet('allow_custom_amount', false)
        ->call('toggleHasTarget')
        ->assertSet('has_target', false)
        ->call('toggleHasEndDate')
        ->assertSet('has_end_date', false)
        ->call('toggleAllowRecurring')
        ->assertSet('allow_recurring', true)
        ->call('toggleAllowCustomAmount')
        ->assertSet('allow_custom_amount', true);
});
