<?php

use App\Enums\UserRole;
use App\Jobs\SendLoginAlertEmail as SendLoginAlertEmailJob;
use App\Listeners\SendLoginAlertEmail as SendLoginAlertEmailListener;
use App\Mail\LoginAlertNotification;
use App\Models\Organization;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Bus::fake([SendLoginAlertEmailJob::class]);
});

afterEach(function () {
    Request::setTrustedProxies([], Request::HEADER_X_FORWARDED_FOR);
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
        ->toContain(appPanelRoute('app.settings.account'))
        ->toContain('target="_blank"')
        ->toContain('rel="noopener noreferrer"');
});

it('resolves city, region and isp from ip-api and labels ipv6 traffic', function () {
    Mail::fake();
    Http::fake([
        'http://ip-api.com/json/2001:db8::*' => Http::response([
            'status' => 'success',
            'country' => 'Malaysia',
            'countryCode' => 'MY',
            'region' => '10',
            'regionName' => 'Selangor',
            'city' => 'Petaling Jaya',
            'zip' => '47301',
            'isp' => 'TM Net',
            'org' => 'Telekom Malaysia',
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
        ipAddress: '2001:db8::1',
        userAgent: 'Mozilla/5.0',
        loggedInAt: CarbonImmutable::now(),
        ipv4Address: '60.52.100.5',
    );

    $job->handle();

    Mail::assertQueued(LoginAlertNotification::class, function (LoginAlertNotification $mail) {
        return $mail->country === 'Malaysia'
            && $mail->city === 'Petaling Jaya'
            && $mail->region === 'Selangor'
            && $mail->isp === 'TM Net'
            && $mail->ipType === 'IPv6'
            && $mail->ipv4Address === '60.52.100.5';
    });
});

it('captures an ipv4 fallback from x-forwarded-for headers in the listener', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->create([
        'role' => UserRole::NgoAdmin,
        'organization_id' => $organization->id,
    ]);

    $request = Request::create('https://app.example.test/dashboard', 'GET', [], [], [], [
        'REMOTE_ADDR' => '10.0.0.5',
        'HTTP_X_FORWARDED_FOR' => '2606:4700:4700::1111, 60.52.100.5, 192.168.1.1',
        'HTTP_USER_AGENT' => 'Mozilla/5.0',
    ]);
    Request::setTrustedProxies(['10.0.0.5'], Request::HEADER_X_FORWARDED_FOR);
    $this->app->instance('request', $request);

    Bus::fake([SendLoginAlertEmailJob::class]);

    $listener = new SendLoginAlertEmailListener;
    $listener->handle(new Login('web', $user, false));

    Bus::assertDispatched(SendLoginAlertEmailJob::class, function (SendLoginAlertEmailJob $job) {
        return $job->ipv4Address === '60.52.100.5';
    });
});
