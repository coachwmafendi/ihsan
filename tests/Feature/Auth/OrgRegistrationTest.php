<?php

use App\Enums\OrganizationStatus;
use App\Livewire\Auth\RegisterOrganization;
use App\Models\Organization;
use Livewire\Livewire;

test('organizations table has facebook_url column', function () {
    expect(Schema::hasColumn('organizations', 'facebook_url'))->toBeTrue();
});

test('registration page renders', function () {
    $this->get('/register-organization')->assertOk();
});

test('org registration creates pending organization', function () {
    Livewire::test(RegisterOrganization::class)
        ->set('name', 'Masjid Al-Falah')
        ->set('registration_type', 'ROS')
        ->set('ros_rob_number', 'PPM-001-10-01012020')
        ->set('sector', 'religion')
        ->set('contact_email', 'admin@alfalah.org')
        ->set('website_url', 'https://alfalah.org')
        ->call('submit')
        ->assertSet('submitted', true);

    $org = Organization::first();
    expect($org->name)->toBe('Masjid Al-Falah')
        ->and($org->registration_type)->toBe('ROS')
        ->and($org->ros_rob_number)->toBe('PPM-001-10-01012020')
        ->and($org->sector)->toBe('religion')
        ->and($org->contact_email)->toBe('admin@alfalah.org')
        ->and($org->website_url)->toBe('https://alfalah.org')
        ->and($org->status)->toBe(OrganizationStatus::Pending);
});

test('org registration with optional facebook url', function () {
    Livewire::test(RegisterOrganization::class)
        ->set('name', 'Persatuan Kebajikan')
        ->set('registration_type', 'ROB')
        ->set('ros_rob_number', 'ROB-12345')
        ->set('sector', 'social_welfare')
        ->set('contact_email', 'info@persatuan.org')
        ->set('website_url', 'https://persatuan.org')
        ->set('facebook_url', 'https://facebook.com/persatuan')
        ->call('submit')
        ->assertSet('submitted', true);

    expect(Organization::first()->facebook_url)->toBe('https://facebook.com/persatuan');
});

test('org registration fails without required fields', function () {
    Livewire::test(RegisterOrganization::class)
        ->call('submit')
        ->assertHasErrors(['name', 'registration_type', 'ros_rob_number', 'sector', 'contact_email', 'website_url']);
});

test('org registration fails with invalid registration_type', function () {
    Livewire::test(RegisterOrganization::class)
        ->set('registration_type', 'INVALID')
        ->call('submit')
        ->assertHasErrors(['registration_type']);
});

test('org registration fails with invalid email', function () {
    Livewire::test(RegisterOrganization::class)
        ->set('contact_email', 'not-an-email')
        ->call('submit')
        ->assertHasErrors(['contact_email']);
});

test('facebook_url is optional', function () {
    Livewire::test(RegisterOrganization::class)
        ->set('name', 'Test Org')
        ->set('registration_type', 'Others')
        ->set('ros_rob_number', 'X-123')
        ->set('sector', 'others')
        ->set('contact_email', 'test@test.com')
        ->set('website_url', 'https://test.com')
        ->call('submit')
        ->assertHasNoErrors(['facebook_url'])
        ->assertSet('submitted', true);
});

test('org registration fails with duplicate ros_rob_number', function () {
    Organization::factory()->create([
        'ros_rob_number' => 'PPM-17282',
        'status' => OrganizationStatus::Pending,
    ]);

    Livewire::test(RegisterOrganization::class)
        ->set('name', 'Test Masjid')
        ->set('registration_type', 'ROS')
        ->set('ros_rob_number', 'PPM-17282')
        ->set('sector', 'religion')
        ->set('contact_email', 'test@test.com')
        ->set('website_url', 'https://test.com')
        ->call('submit')
        ->assertHasErrors(['ros_rob_number']);
});
