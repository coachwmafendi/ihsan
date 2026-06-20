<?php

use App\Enums\UserRole;
use App\Jobs\SendLoginAlertEmail as SendLoginAlertEmailJob;
use App\Listeners\SendLoginAlertEmail as SendLoginAlertEmailListener;
use App\Mail\LoginAlertNotification;
use App\Models\Organization;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Bus::fake([SendLoginAlertEmailJob::class]);
});

it('dispatches login alert job for ngo admin with organization', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->create([
        'role' => UserRole::NgoAdmin,
        'organization_id' => $organization->id,
    ]);

    $listener = new SendLoginAlertEmailListener;
    $listener->handle(new Login('web', $user, false));

    Bus::assertDispatched(SendLoginAlertEmailJob::class, function (SendLoginAlertEmailJob $job) use ($user) {
        return $job->user->is($user);
    });
});

it('does not dispatch login alert job for super admin', function () {
    $user = User::factory()->create([
        'role' => UserRole::SuperAdmin,
        'organization_id' => null,
    ]);

    $listener = new SendLoginAlertEmailListener;
    $listener->handle(new Login('web', $user, false));

    Bus::assertNotDispatched(SendLoginAlertEmailJob::class);
});

it('does not dispatch login alert job for ngo admin without organization', function () {
    $user = User::factory()->create([
        'role' => UserRole::NgoAdmin,
        'organization_id' => null,
    ]);

    $listener = new SendLoginAlertEmailListener;
    $listener->handle(new Login('web', $user, false));

    Bus::assertNotDispatched(SendLoginAlertEmailJob::class);
});

it('sends login alert email with country resolved from ip api', function () {
    Mail::fake();
    Http::fake([
        'http://ip-api.com/json/8.8.8.8*' => Http::response([
            'status' => 'success',
            'country' => 'United States',
            'countryCode' => 'US',
        ]),
    ]);

    $organization = Organization::factory()->create();
    $user = User::factory()->create([
        'role' => UserRole::NgoAdmin,
        'organization_id' => $organization->id,
        'email' => 'admin@example.org',
    ]);

    $job = new SendLoginAlertEmailJob(
        user: $user,
        ipAddress: '8.8.8.8',
        userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        loggedInAt: CarbonImmutable::parse('2026-06-20 14:30:00'),
    );

    $job->handle();

    Mail::assertQueued(LoginAlertNotification::class, function (LoginAlertNotification $mail) use ($organization, $user) {
        return $mail->organization->is($organization)
            && $mail->country === 'United States'
            && $mail->ipAddress === '8.8.8.8'
            && $mail->browser === 'Browser / Windows'
            && $mail->hasTo($user->email);
    });
});

it('falls back to unknown country for private ip', function () {
    Mail::fake();
    Http::fake();

    $organization = Organization::factory()->create();
    $user = User::factory()->create([
        'role' => UserRole::NgoAdmin,
        'organization_id' => $organization->id,
    ]);

    $job = new SendLoginAlertEmailJob(
        user: $user,
        ipAddress: '127.0.0.1',
        userAgent: 'Mozilla/5.0',
        loggedInAt: CarbonImmutable::now(),
    );

    $job->handle();

    Mail::assertQueued(LoginAlertNotification::class, function (LoginAlertNotification $mail) {
        return $mail->country === 'Unknown';
    });

    Http::assertNothingSent();
});

it('includes a link to the account page in the email body', function () {
    $organization = Organization::factory()->create();

    $mail = new LoginAlertNotification(
        organization: $organization,
        country: 'Malaysia',
        ipAddress: '1.2.3.4',
        browser: 'Chrome / macOS',
        loggedInAt: CarbonImmutable::now(),
    );

    $html = $mail->render();

    expect($html)
        ->toContain(route('app.settings.account'))
        ->toContain('target="_blank"')
        ->toContain('rel="noopener noreferrer"');
});
