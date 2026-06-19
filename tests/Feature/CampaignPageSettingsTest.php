<?php

declare(strict_types=1);

use App\Enums\UserRole;
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
});

it('renders campaign page settings with required labels', function () {
    $campaign = Campaign::factory()->create([
        'organization_id' => $this->organization->id,
    ]);

    $this->actingAs($this->user);

    Livewire::test(CampaignEdit::class, ['campaign' => $campaign])
        ->set('activeTab', 'campaign-page')
        ->assertSee('Campaign Page')
        ->assertSee('Thank you screen')
        ->assertSee('Show supporters the default thank you screen')
        ->assertSee('Redirect supporters to a specific URL')
        ->assertSee('Sharing URL')
        ->assertSee('Default sharing message');
});
