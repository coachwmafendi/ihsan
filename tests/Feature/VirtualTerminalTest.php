<?php

use App\Actions\Stripe\ProcessVirtualTerminalDonation;
use App\Actions\Stripe\ProcessVirtualTerminalSubscription;
use App\Filament\App\Pages\VirtualTerminal;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\Subscription;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->user = User::factory()->create([
        'organization_id' => $this->organization->id,
    ]);
    $this->actingAs($this->user);
});

test('virtual terminal page is accessible by org admin', function () {
    // TODO: use route() helper once VirtualTerminal page is registered
    $response = $this->get('/app/virtual-terminal');
    $response->assertOk();
});

test('virtual terminal page preloads supporter from query param', function () {
    $donor = Donor::factory()->create([
        'name' => 'Ahmad Ali',
        'email' => 'ahmad@example.com',
    ]);

    // TODO: use route() helper once VirtualTerminal page is registered
    $response = $this->get("/app/virtual-terminal?vt-supporter={$donor->public_id}");
    $response->assertOk();
    $response->assertSee('Ahmad Ali');
    $response->assertSee('ahmad@example.com');
});

test('one-time donation creates donation record for existing donor', function () {
    $campaign = Campaign::factory()->create([
        'organization_id' => $this->organization->id,
    ]);
    $donor = Donor::factory()->create([
        'name' => 'Ahmad Ali',
        'email' => 'ahmad@example.com',
    ]);

    $donation = Donation::make([
        'campaign_id' => $campaign->id,
        'donor_id' => $donor->id,
        'gross_amount' => 50.00,
        'base_amount' => 50.00,
        'currency' => 'myr',
        'base_currency' => 'myr',
        'status' => \App\Enums\DonationStatus::Succeeded,
        'type' => \App\Enums\DonationType::OneTime,
        'source' => 'virtual_terminal',
        'stripe_payment_intent_id' => 'pi_test_'.uniqid(),
    ]);

    $mock = Mockery::mock(ProcessVirtualTerminalDonation::class);
    $mock->shouldReceive('handle')->once()->andReturnUsing(function () use ($donation) {
        $donation->save();

        return $donation;
    });
    app()->instance(ProcessVirtualTerminalDonation::class, $mock);

    Livewire::test(VirtualTerminal::class)
        ->set('formData.campaign_id', (string) $campaign->id)
        ->set('formData.frequency', 'once')
        ->set('formData.amount', '50.00')
        ->set('formData.first_name', 'Ahmad')
        ->set('formData.last_name', 'Ali')
        ->set('formData.email', 'ahmad@example.com')
        ->callAction('processDonation');

    $this->assertDatabaseHas('donations', [
        'campaign_id' => $campaign->id,
        'donor_id' => $donor->id,
        'gross_amount' => '50.00',
        'source' => 'virtual_terminal',
    ]);
});

test('unauthenticated user cannot access virtual terminal', function () {
    auth()->logout();

    $response = $this->get('/app/virtual-terminal');
    $response->assertRedirect('/login');
});

test('user without organization cannot access virtual terminal', function () {
    $user = User::factory()->create(['organization_id' => null]);
    $this->actingAs($user);

    $response = $this->get('/app/virtual-terminal');
    $response->assertForbidden();
});

test('virtual terminal validates required fields', function () {
    $campaign = Campaign::factory()->create([
        'organization_id' => $this->organization->id,
    ]);

    Livewire::test(VirtualTerminal::class)
        ->set('formData.campaign_id', (string) $campaign->id)
        ->set('formData.frequency', 'once')
        ->set('formData.amount', '')
        ->set('formData.first_name', '')
        ->set('formData.last_name', '')
        ->set('formData.email', '')
        ->callAction('processDonation')
        ->assertNotDispatched('close-modal'); // Action should not succeed
});

test('new donor is created when email does not match existing donor', function () {
    $campaign = Campaign::factory()->create([
        'organization_id' => $this->organization->id,
    ]);

    $mock = Mockery::mock(ProcessVirtualTerminalDonation::class);
    $mock->shouldReceive('handle')->once()->andReturnUsing(function ($campaignId, $amount, $firstName, $lastName, $email, $organization) {
        $donor = Donor::create([
            'name' => trim("{$firstName} {$lastName}"),
            'email' => $email,
        ]);

        $donation = Donation::create([
            'campaign_id' => $campaignId,
            'donor_id' => $donor->id,
            'gross_amount' => $amount,
            'base_amount' => $amount,
            'currency' => 'myr',
            'base_currency' => 'myr',
            'status' => \App\Enums\DonationStatus::Succeeded,
            'type' => \App\Enums\DonationType::OneTime,
            'stripe_payment_intent_id' => 'pi_test_'.uniqid(),
        ]);

        return $donation;
    });
    app()->instance(ProcessVirtualTerminalDonation::class, $mock);

    Livewire::test(VirtualTerminal::class)
        ->set('formData.campaign_id', (string) $campaign->id)
        ->set('formData.frequency', 'once')
        ->set('formData.amount', '30.00')
        ->set('formData.first_name', 'Siti')
        ->set('formData.last_name', 'Nor')
        ->set('formData.email', 'newdonor@example.com')
        ->callAction('processDonation');

    $this->assertDatabaseHas('donors', [
        'email' => 'newdonor@example.com',
        'name' => 'Siti Nor',
    ]);

    $this->assertDatabaseHas('donations', [
        'campaign_id' => $campaign->id,
        'gross_amount' => '30.00',
    ]);
});

test('donation created via virtual terminal has source tracking', function () {
    $campaign = Campaign::factory()->create([
        'organization_id' => $this->organization->id,
    ]);
    $donor = Donor::factory()->create([
        'name' => 'Ahmad Ali',
        'email' => 'ahmad@example.com',
    ]);

    $donation = Donation::make([
        'campaign_id' => $campaign->id,
        'donor_id' => $donor->id,
        'gross_amount' => 50.00,
        'base_amount' => 50.00,
        'currency' => 'myr',
        'base_currency' => 'myr',
        'status' => \App\Enums\DonationStatus::Succeeded,
        'type' => \App\Enums\DonationType::OneTime,
        'source' => 'virtual_terminal',
        'stripe_payment_intent_id' => 'pi_test_'.uniqid(),
    ]);

    $mock = Mockery::mock(ProcessVirtualTerminalDonation::class);
    $mock->shouldReceive('handle')->once()->andReturnUsing(function () use ($donation) {
        $donation->save();

        return $donation;
    });
    app()->instance(ProcessVirtualTerminalDonation::class, $mock);

    Livewire::test(VirtualTerminal::class)
        ->set('formData.campaign_id', (string) $campaign->id)
        ->set('formData.frequency', 'once')
        ->set('formData.amount', '50.00')
        ->set('formData.first_name', 'Ahmad')
        ->set('formData.last_name', 'Ali')
        ->set('formData.email', 'ahmad@example.com')
        ->callAction('processDonation');

    $this->assertDatabaseHas('donations', [
        'campaign_id' => $campaign->id,
        'donor_id' => $donor->id,
        'source' => 'virtual_terminal',
    ]);
});

test('email search finds existing supporter', function () {
    $donor = Donor::factory()->create([
        'name' => 'Ahmad Ali',
        'email' => 'ahmad@example.com',
    ]);
    // Need at least one donation for the org-scoped query
    $campaign = Campaign::factory()->create([
        'organization_id' => $this->organization->id,
    ]);
    \App\Models\Donation::factory()->create([
        'campaign_id' => $campaign->id,
        'donor_id' => $donor->id,
        'gross_amount' => 10,
        'status' => \App\Enums\DonationStatus::Succeeded,
    ]);

    $component = Livewire::test(VirtualTerminal::class)
        ->set('formData.email', 'ahmad@example.com')
        ->call('searchDonorByEmail');

    $component->assertSet('searchedDonor.id', $donor->id);
});

test('payment method defaults to new card and can be changed', function () {
    $component = Livewire::test(VirtualTerminal::class);

    $component->assertSet('formData.payment_method', 'new_card');

    $component->set('formData.payment_method', 'pm_test_123');
    $component->assertSet('formData.payment_method', 'pm_test_123');
});

test('monthly donation creates subscription record', function () {
    $campaign = Campaign::factory()->create([
        'organization_id' => $this->organization->id,
    ]);
    $donor = Donor::factory()->create([
        'name' => 'Ahmad Ali',
        'email' => 'ahmad@example.com',
    ]);

    $subscription = Subscription::make([
        'campaign_id' => $campaign->id,
        'donor_id' => $donor->id,
        'amount' => 25.00,
        'currency' => 'myr',
        'interval' => \App\Enums\SubscriptionInterval::Monthly,
        'status' => \App\Enums\SubscriptionStatus::Active,
        'source' => 'virtual_terminal',
        'stripe_subscription_id' => 'sub_test_'.uniqid(),
        'stripe_price_id' => 'price_test_'.uniqid(),
        'started_at' => now(),
        'current_period_start' => now(),
    ]);

    $mock = Mockery::mock(ProcessVirtualTerminalSubscription::class);
    $mock->shouldReceive('handle')->once()->andReturnUsing(function () use ($subscription) {
        $subscription->save();

        return $subscription;
    });
    app()->instance(ProcessVirtualTerminalSubscription::class, $mock);

    Livewire::test(VirtualTerminal::class)
        ->set('formData.campaign_id', (string) $campaign->id)
        ->set('formData.frequency', 'monthly')
        ->set('formData.amount', '25.00')
        ->set('formData.first_name', 'Ahmad')
        ->set('formData.last_name', 'Ali')
        ->set('formData.email', 'ahmad@example.com')
        ->callAction('processDonation');

    $this->assertDatabaseHas('subscriptions', [
        'campaign_id' => $campaign->id,
        'donor_id' => $donor->id,
        'amount' => '25.00',
        'interval' => 'monthly',
        'source' => 'virtual_terminal',
    ]);
});
