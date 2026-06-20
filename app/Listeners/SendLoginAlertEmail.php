<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\UserRole;
use App\Jobs\SendLoginAlertEmail as SendLoginAlertEmailJob;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Login;

class SendLoginAlertEmail
{
    public function handle(Login $event): void
    {
        $user = $event->user;

        if (! $user instanceof User) {
            return;
        }

        if ($user->role !== UserRole::NgoAdmin || $user->organization_id === null) {
            return;
        }

        $request = request();

        SendLoginAlertEmailJob::dispatch(
            user: $user,
            ipAddress: (string) $request->ip(),
            userAgent: (string) $request->userAgent(),
            loggedInAt: CarbonImmutable::now(),
        );
    }
}
