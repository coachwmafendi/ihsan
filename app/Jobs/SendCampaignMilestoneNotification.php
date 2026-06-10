<?php

namespace App\Jobs;

use App\Enums\UserRole;
use App\Mail\CampaignMilestoneNotification;
use App\Models\Campaign;
use App\Models\User;
use App\Support\MailtrapThrottle;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendCampaignMilestoneNotification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Campaign $campaign,
        public float $previousCollected,
    ) {}

    public function handle(): void
    {
        $this->campaign->loadMissing('organization');

        $org = $this->campaign->organization;

        if ($org === null) {
            return;
        }

        $settings = $org->settings ?? [];

        if (! ($settings['notify_campaign_milestone'] ?? false)) {
            return;
        }

        if (! $this->campaign->has_target || ! ($this->campaign->target_amount > 0)) {
            return;
        }

        $target = (float) $this->campaign->target_amount;
        $current = (float) $this->campaign->collected_amount;
        $previous = $this->previousCollected;

        $previousPct = ($previous / $target) * 100;
        $currentPct = ($current / $target) * 100;

        $milestones = [25, 50, 75, 100];

        if ($currentPct > 100) {
            for ($i = 150; $i <= $currentPct + 50; $i += 50) {
                $milestones[] = $i;
            }
        }

        $notified = $this->campaign->milestones_notified ?? [];
        $newMilestones = [];

        foreach ($milestones as $ms) {
            if ($previousPct < $ms && $currentPct >= $ms && ! in_array((string) $ms, $notified)) {
                $newMilestones[] = $ms;
            }
        }

        if (empty($newMilestones)) {
            return;
        }

        $highest = (string) max($newMilestones);

        $admins = User::query()
            ->where('organization_id', $org->getKey())
            ->where('role', UserRole::NgoAdmin)
            ->get();

        foreach ($admins as $admin) {
            MailtrapThrottle::throttle();
            Mail::to($admin->email)->queue(
                new CampaignMilestoneNotification(
                    campaign: $this->campaign,
                    milestone: $highest,
                    percent: number_format($currentPct, 1),
                    collected: number_format($current, 2),
                    target: number_format($target, 2),
                )
            );
        }

        $this->campaign->update([
            'milestones_notified' => array_unique([...$notified, $highest]),
        ]);
    }
}
