<?php

use App\Enums\SubscriptionStatus;
use App\Livewire\App\Subscriptions\SubscriptionIndex;
use App\Models\Campaign;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->user = User::factory()->create([
        'organization_id' => $this->organization->id,
    ]);
    $this->campaign = Campaign::factory()->create([
        'organization_id' => $this->organization->id,
    ]);
    $this->donor = Donor::factory()->create();
});

it('renders the recurring plans index with a status column', function () {
    Subscription::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
    ]);

    Livewire::actingAs($this->user)
        ->test(SubscriptionIndex::class)
        ->assertStatus(200)
        ->assertSee('Status')
        ->assertSee('Active');
});

it('displays status icon and label for each status variant', function () {
    foreach (SubscriptionStatus::cases() as $status) {
        Subscription::factory()->create([
            'campaign_id' => $this->campaign->id,
            'donor_id' => $this->donor->id,
            'status' => $status,
        ]);
    }

    $response = Livewire::actingAs($this->user)
        ->test(SubscriptionIndex::class)
        ->assertStatus(200);

    foreach (SubscriptionStatus::cases() as $status) {
        $response->assertSee($status->getLabel());
    }
});
