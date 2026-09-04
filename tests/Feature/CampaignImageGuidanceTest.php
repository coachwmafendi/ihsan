<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Livewire\App\Campaigns\CampaignCreate;
use App\Livewire\App\Campaigns\CampaignEdit;
use App\Models\Campaign;
use App\Models\Organization;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->user = User::factory()->create([
        'organization_id' => $this->organization->id,
        'role' => UserRole::NgoAdmin,
    ]);

    $this->actingAs($this->user);
});

it('shows the recommended image size on the campaign create form', function () {
    Livewire::test(CampaignCreate::class)
        ->assertSee('1600 &times; 900 px', escape: false)
        ->assertSee('Keep the subject centred');
});

it('shows the recommended image size on the campaign edit form', function () {
    $campaign = Campaign::factory()->create([
        'organization_id' => $this->organization->id,
    ]);

    Livewire::test(CampaignEdit::class, ['campaign' => $campaign])
        ->assertSee('1600 &times; 900 px', escape: false)
        ->assertSee('Keep the subject centred');
});

it('explains the checkout modal crop sizes in the guidance tooltip', function () {
    Livewire::test(CampaignCreate::class)
        ->assertSee('520 &times; 192 px', escape: false)
        ->assertSee('800 &times; 360 px', escape: false)
        ->assertSee('552 &times; 256 px', escape: false);
});

it('shows separate guidance for the content section image and logo', function () {
    $campaign = Campaign::factory()->create([
        'organization_id' => $this->organization->id,
    ]);

    Livewire::test(CampaignEdit::class, ['campaign' => $campaign])
        ->assertSee('1200 &times; 900 px', escape: false)
        ->assertSee('544 &times; 448 px', escape: false)
        ->assertSee('240 &times; 80 px', escape: false);
});
