<?php

use App\Models\User;
use App\Notifications\InviteOrganizationAdmin;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('greets the new admin by email and states the registration is approved', function () {
    $user = User::factory()->create();

    $message = (new InviteOrganizationAdmin('Darul Mujtaba'))->toMail($user);

    expect($message->greeting)->toBe('Hi '.$user->email.',');
    expect($message->introLines)->toContain('Your organization (Darul Mujtaba) registration application has been approved.');
});
