<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\UserRole;
use App\Jobs\SendLoginAlertEmail as SendLoginAlertEmailJob;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\Request;

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
            ipv4Address: $this->resolvePublicIpv4($request),
        );
    }

    private function resolvePublicIpv4(Request $request): ?string
    {
        foreach ($request->ips() as $candidate) {
            if (filter_var($candidate, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                return $candidate;
            }
        }

        return null;
    }
}
