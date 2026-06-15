<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->user = User::factory()->create([
        'organization_id' => $this->organization->id,
    ]);
});

it('redirects guests to login', function () {
    get('/app/dashboard')
        ->assertRedirect('/login');
});

it('renders successfully for authenticated users', function () {
    actingAs($this->user)
        ->get('/app/dashboard')
        ->assertOk()
        ->assertSee('Dashboard')
        ->assertSee('Overview of your fundraising activity');
});

it('displays organization stats', function () {
    actingAs($this->user)
        ->get('/app/dashboard')
        ->assertOk()
        ->assertSee('Total Donations')
        ->assertSee('Donations')
        ->assertSee('Donors')
        ->assertSee('Campaigns');
});

it('shows recent donations', function () {
    actingAs($this->user)
        ->get('/app/dashboard')
        ->assertOk()
        ->assertSee('Recent activity');
});

it('shows quick action buttons', function () {
    actingAs($this->user)
        ->get('/app/dashboard')
        ->assertOk()
        ->assertSee('Create Campaign')
        ->assertSee('View Donations')
        ->assertSee('Virtual Terminal')
        ->assertSee('Opens in new tab', false);
});

it('has sidebar navigation', function () {
    actingAs($this->user)
        ->get('/app/dashboard')
        ->assertOk()
        ->assertSee('Fundraise')
        ->assertSee('Finance')
        ->assertSee('Supporters')
        ->assertSee('Organization');
});

it('opens virtual terminal navigation in a new tab', function () {
    actingAs($this->user)
        ->get('/app/dashboard')
        ->assertOk()
        ->assertSee('href="/app/virtual-terminal"', false)
        ->assertSee('target="_blank"', false)
        ->assertSee('Opens in new tab', false);
});

it('dispatches the open campaign modal event from the dashboard', function () {
    Livewire::actingAs($this->user)
        ->test('app.dashboard')
        ->assertSee('Create Campaign')
        ->call('openCreateCampaignModal')
        ->assertDispatched('open-create-campaign-modal');
});
