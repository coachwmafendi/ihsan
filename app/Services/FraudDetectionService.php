<?php

namespace App\Services;

use App\Mail\FraudAlertNotification;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Fraud\BlockedDonation;
use App\Models\Fraud\FraudAttempt;
use App\Models\Fraud\FraudRule;
use Illuminate\Support\Facades\Mail;

class FraudDetectionService
{
    private array $rules;

    public function __construct(private readonly ?Donor $donor = null, private readonly ?Donation $donation = null)
    {
        $this->rules = $this->loadRules();
    }

    public function assess(array $context = []): array
    {
        $results = [];
        $overallAction = 'allow';

        foreach ($this->rules as $rule) {
            if (! $rule->is_active) {
                continue;
            }

            $result = match ($rule->rule_type) {
                'velocity' => $this->checkVelocity($rule->config),
                'amount' => $this->checkAmount($rule->config, $context['amount'] ?? 0),
                'country' => $this->checkCountry($rule->config, $context['billing_country'] ?? null),
                'risk_score' => ['action' => 'pass', 'reason' => null], // Stripe Radar handled separately
                default => ['action' => 'pass', 'reason' => null],
            };

            if ($result['action'] !== 'pass') {
                $results[] = array_merge($result, ['rule_type' => $rule->rule_type, 'rule_action' => $rule->action]);

                if ($rule->action === 'block') {
                    $overallAction = 'block';
                } elseif ($overallAction !== 'block' && $rule->action === 'flag') {
                    $overallAction = 'flag';
                }
            }
        }

        return [
            'action' => $overallAction,
            'matches' => $results,
        ];
    }

    private function loadRules(): array
    {
        $orgId = $this->donation?->campaign?->organization_id;

        // Fallback: try to get org from donor's recent donations
        if ($orgId === null && $this->donor !== null) {
            $recentDonation = $this->donor->donations()
                ->with('campaign')
                ->latest()
                ->first();

            $orgId = $recentDonation?->campaign?->organization_id;
        }

        if ($orgId === null) {
            return [];
        }

        return FraudRule::query()
            ->where('organization_id', $orgId)
            ->orWhereNull('organization_id')
            ->orderBy('created_at')
            ->get()
            ->all();
    }

    private function checkVelocity(array $config): array
    {
        $threshold = $config['threshold'] ?? 5;
        $windowMinutes = $config['window_minutes'] ?? 60;

        if ($this->donor === null) {
            return ['action' => 'pass', 'reason' => null];
        }

        $count = $this->donor->donations()
            ->where('created_at', '>=', now()->subMinutes($windowMinutes))
            ->count();

        if ($count >= $threshold) {
            return [
                'action' => 'triggered',
                'reason' => "Velocity limit exceeded: {$count} donations in {$windowMinutes} minutes",
                'details' => ['count' => $count, 'threshold' => $threshold],
            ];
        }

        return ['action' => 'pass', 'reason' => null];
    }

    private function checkAmount(array $config, float $amount): array
    {
        $maxAmount = $config['max_amount'] ?? 10000;
        $currency = $config['currency'] ?? 'myr';

        if ($amount > $maxAmount) {
            return [
                'action' => 'triggered',
                'reason' => "Amount exceeds threshold: {$amount} {$currency} (max: {$maxAmount})",
                'details' => ['amount' => $amount, 'max' => $maxAmount],
            ];
        }

        return ['action' => 'pass', 'reason' => null];
    }

    private function checkCountry(array $config, ?string $country): array
    {
        $blockedCountries = $config['blocked_countries'] ?? [];

        if ($country === null || ! in_array(strtoupper($country), $blockedCountries, true)) {
            return ['action' => 'pass', 'reason' => null];
        }

        return [
            'action' => 'triggered',
            'reason' => "Country blocked: {$country}",
            'details' => ['country' => $country],
        ];
    }

    public static function logAttempt(array $data): FraudAttempt
    {
        return FraudAttempt::create($data);
    }

    public static function blockDonation(Donation $donation, string $reason): BlockedDonation
    {
        return BlockedDonation::create([
            'donation_id' => $donation->id,
            'reason' => $reason,
            'review_status' => 'pending',
        ]);
    }

    public static function seedDefaultRules(int $organizationId): void
    {
        $defaults = [
            [
                'organization_id' => $organizationId,
                'rule_type' => 'velocity',
                'config' => ['threshold' => 5, 'window_minutes' => 60],
                'action' => 'flag',
                'is_active' => true,
            ],
            [
                'organization_id' => $organizationId,
                'rule_type' => 'amount',
                'config' => ['max_amount' => 10000, 'currency' => 'myr'],
                'action' => 'flag',
                'is_active' => true,
            ],
            [
                'organization_id' => $organizationId,
                'rule_type' => 'country',
                'config' => ['blocked_countries' => []],
                'action' => 'block',
                'is_active' => false,
            ],
            [
                'organization_id' => $organizationId,
                'rule_type' => 'risk_score',
                'config' => ['threshold' => 65],
                'action' => 'block',
                'is_active' => true,
            ],
        ];

        foreach ($defaults as $rule) {
            FraudRule::query()->firstOrCreate(
                [
                    'organization_id' => $rule['organization_id'],
                    'rule_type' => $rule['rule_type'],
                ],
                $rule
            );
        }
    }

    public static function notifyAdmins(Donation $donation, string $reason, string $action): void
    {
        $organization = $donation->campaign?->organization;

        $recipients = [];

        if ($organization?->contact_email) {
            $recipients[] = $organization->contact_email;
        }

        $superAdminEmail = config('app.admin_email');
        if ($superAdminEmail) {
            $recipients[] = $superAdminEmail;
        }

        foreach (array_unique($recipients) as $email) {
            Mail::to($email)->queue(new FraudAlertNotification($donation, $reason, $action));
        }
    }
}
