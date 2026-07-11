<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Filament\Pages\EmailSettings;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Livewire\Livewire;

it('encrypts email secrets at rest', function () {
    Setting::set('mail_password', 'secret-password');

    $storedValue = Setting::where('key', 'mail_password')->value('value');

    expect($storedValue)->not->toBe('secret-password');
    expect(Crypt::decryptString($storedValue))->toBe('secret-password');
});

it('decrypts email secrets when read', function () {
    Setting::set('mail_password', 'secret-password');

    expect(Setting::get('mail_password'))->toBe('secret-password');
});

it('leaves non secret settings unencrypted', function () {
    Setting::set('mail_driver', 'smtp');

    expect(Setting::where('key', 'mail_driver')->value('value'))->toBe('smtp');
    expect(Setting::get('mail_driver'))->toBe('smtp');
});

it('shows decrypted secrets in the email settings form', function () {
    Setting::set('mail_driver', 'smtp');
    Setting::set('mail_password', 'secret-password');
    Setting::set('mail_from_address', 'hello@getihsan.my');
    Setting::set('mail_from_name', 'Ihsan');
    Setting::set('mail_throttle_seconds', 0);

    $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);

    Livewire::actingAs($admin)
        ->test(EmailSettings::class)
        ->assertSet('data.mail_password', 'secret-password');
});
