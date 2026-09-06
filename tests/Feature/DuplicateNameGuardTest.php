<?php

declare(strict_types=1);

use App\Enums\CampaignStatus;
use App\Enums\ElementType;
use App\Enums\UserRole;
use App\Livewire\App\Campaigns\CampaignCreate;
use App\Livewire\App\Campaigns\CampaignEdit;
use App\Livewire\App\Elements\ElementCreate;
use App\Livewire\App\Elements\ElementEdit;
use App\Livewire\App\Elements\ElementIndex;
use App\Models\Campaign;
use App\Models\Element;
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

it('rejects a campaign title already used in the organization', function () {
    Campaign::factory()->for($this->organization)->create([
        'title' => 'Tahfiz An Nur Development Wakaf Appeal',
    ]);

    Livewire::test(CampaignCreate::class)
        ->set('title', 'Tahfiz An Nur Development Wakaf Appeal')
        ->set('status', 'draft')
        ->call('save')
        ->assertHasErrors('title');

    expect(Campaign::query()->where('organization_id', $this->organization->id)->count())->toBe(1);
});

it('compares campaign titles without regard to case or padding', function () {
    Campaign::factory()->for($this->organization)->create([
        'title' => 'Tahfiz An Nur Development Wakaf Appeal',
    ]);

    Livewire::test(CampaignCreate::class)
        ->set('title', '  tahfiz an nur development wakaf appeal ')
        ->set('status', 'draft')
        ->call('save')
        ->assertHasErrors('title');
});

it('allows another organization to use the same campaign title', function () {
    $otherOrganization = Organization::factory()->create();
    Campaign::factory()->for($otherOrganization)->create([
        'title' => 'Tahfiz An Nur Development Wakaf Appeal',
    ]);

    Livewire::test(CampaignCreate::class)
        ->set('title', 'Tahfiz An Nur Development Wakaf Appeal')
        ->set('status', 'draft')
        ->call('save')
        ->assertHasNoErrors('title');
});

it('frees a campaign title once the original is archived', function () {
    Campaign::factory()->for($this->organization)->create([
        'title' => 'Ramadan Food Drive',
        'status' => CampaignStatus::Archived,
    ]);

    Livewire::test(CampaignCreate::class)
        ->set('title', 'Ramadan Food Drive')
        ->set('status', 'draft')
        ->call('save')
        ->assertHasNoErrors('title');
});

it('lets a campaign keep its own title while editing', function () {
    $campaign = Campaign::factory()->for($this->organization)->create([
        'title' => 'Ramadan Food Drive',
    ]);

    Livewire::test(CampaignEdit::class, ['campaign' => $campaign])
        ->set('title', 'Ramadan Food Drive')
        ->call('save')
        ->assertHasNoErrors('title');
});

it('rejects renaming a campaign onto a sibling title', function () {
    Campaign::factory()->for($this->organization)->create(['title' => 'Ramadan Food Drive']);
    $campaign = Campaign::factory()->for($this->organization)->create(['title' => 'Qurban 2026']);

    Livewire::test(CampaignEdit::class, ['campaign' => $campaign])
        ->set('title', 'Ramadan Food Drive')
        ->call('save')
        ->assertHasErrors('title');

    expect($campaign->fresh()->title)->toBe('Qurban 2026');
});

it('rejects an element name already used in the organization', function () {
    $campaign = Campaign::factory()->for($this->organization)->create();
    Element::factory()->for($this->organization)->for($campaign)->create([
        'name' => 'Donate button',
    ]);

    Livewire::test(ElementCreate::class)
        ->set('campaign_id', $campaign->id)
        ->set('type', 'button')
        ->set('name', 'Donate button')
        ->call('save')
        ->assertHasErrors('name');

    expect(Element::query()->where('organization_id', $this->organization->id)->count())->toBe(1);
});

it('rejects a duplicate element name from the quick create modal', function () {
    $campaign = Campaign::factory()->for($this->organization)->create();
    Element::factory()->for($this->organization)->for($campaign)->create([
        'name' => 'Donate button',
    ]);

    Livewire::test(ElementIndex::class)
        ->set('newCampaignId', $campaign->id)
        ->set('newType', ElementType::Button->value)
        ->set('newName', 'Donate button')
        ->call('createElement')
        ->assertHasErrors('newName');

    expect(Element::query()->where('organization_id', $this->organization->id)->count())->toBe(1);
});

it('lets an element keep its own name while editing', function () {
    $campaign = Campaign::factory()->for($this->organization)->create();
    $element = Element::factory()->for($this->organization)->for($campaign)->create([
        'name' => 'Donate button',
    ]);

    Livewire::test(ElementEdit::class, ['element' => $element])
        ->set('name', 'Donate button')
        ->call('save')
        ->assertHasNoErrors('name');
});

it('frees an element name once the original is archived', function () {
    $campaign = Campaign::factory()->for($this->organization)->create();
    Element::factory()->for($this->organization)->for($campaign)->create([
        'name' => 'Donate button',
        'archived_at' => now(),
    ]);

    Livewire::test(ElementCreate::class)
        ->set('campaign_id', $campaign->id)
        ->set('type', 'button')
        ->set('name', 'Donate button')
        ->call('save')
        ->assertHasNoErrors('name');
});

it('still saves a campaign that was duplicated before the rule existed', function () {
    $original = Campaign::factory()->for($this->organization)->create(['title' => 'Legacy Appeal']);
    $duplicate = Campaign::factory()->for($this->organization)->create(['title' => 'Legacy Appeal']);

    Livewire::test(CampaignEdit::class, ['campaign' => $duplicate])
        ->set('thank_you_message', 'Thank you for your support')
        ->call('save')
        ->assertHasNoErrors('title');

    expect($duplicate->fresh()->thank_you_message)->toBe('Thank you for your support')
        ->and($original->fresh()->title)->toBe('Legacy Appeal');
});
