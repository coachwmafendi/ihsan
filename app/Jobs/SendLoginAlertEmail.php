<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\UserRole;
use App\Mail\LoginAlertNotification;
use App\Models\User;
use App\Support\Browser;
use App\Support\MailtrapThrottle;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class SendLoginAlertEmail implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public User $user,
        public string $ipAddress,
        public string $userAgent,
        public CarbonImmutable $loggedInAt,
        public ?string $ipv4Address = null,
    ) {}

    public function handle(): void
    {
        $user = User::query()->whereKey($this->user->getKey())->first();

        if ($user === null || $user->role !== UserRole::NgoAdmin || $user->organization_id === null) {
            return;
        }

        $organization = $user->organization;

        if ($organization === null) {
            return;
        }

        $location = $this->resolveLocation($this->ipAddress);
        $browser = Browser::parse($this->userAgent);

        $delay = MailtrapThrottle::delaySeconds();

        Mail::to($user->email)
            ->later(
                now()->addSeconds($delay),
                new LoginAlertNotification(
                    organization: $organization,
                    country: $location['country'],
                    ipAddress: $this->ipAddress,
                    browser: $browser,
                    loggedInAt: $this->loggedInAt,
                    ipType: $this->ipType($this->ipAddress),
                    ipv4Address: $this->ipv4Address,
                    city: $location['city'] ?? '',
                    region: $location['regionName'] ?? '',
                    isp: $location['isp'] ?? '',
                )
            );
    }

    /** @return array<string, string> */
    private function resolveLocation(string $ipAddress): array
    {
        if ($this->isPrivateIp($ipAddress)) {
            return [
                'country' => 'Unknown',
                'city' => '',
                'regionName' => '',
                'isp' => '',
            ];
        }

        try {
            $response = Http::timeout(3)
                ->connectTimeout(2)
                ->get("http://ip-api.com/json/{$ipAddress}", [
                    'fields' => 'status,country,countryCode,region,regionName,city,zip,isp,org,query,message',
                ]);

            $data = $response->json();

            if (is_array($data) && ($data['status'] ?? '') === 'success' && filled($data['country'] ?? null)) {
                return [
                    'country' => $data['country'],
                    'city' => $data['city'] ?? '',
                    'regionName' => $data['regionName'] ?? '',
                    'isp' => $data['isp'] ?? '',
                ];
            }
        } catch (\Throwable) {
            // fall through to unknown
        }

        return [
            'country' => 'Unknown',
            'city' => '',
            'regionName' => '',
            'isp' => '',
        ];
    }

    private function isPrivateIp(string $ipAddress): bool
    {
        if ($ipAddress === '127.0.0.1' || $ipAddress === '::1') {
            return true;
        }

        return filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    }

    private function ipType(string $ipAddress): string
    {
        return filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false ? 'IPv6' : 'IPv4';
    }
}
