<?php

namespace App\Jobs;

use App\Enums\DonationStatus;
use App\Enums\TrackingProvider;
use App\Models\Donation;
use App\Models\TrackingConfiguration;
use App\Models\TrackingEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class SendSnapchatConversionEvent implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [10, 30, 60];

    public function __construct(public Donation $donation) {}

    public function handle(): void
    {
        $organization = $this->donation->campaign?->organization;

        if (! $organization) {
            return;
        }

        if ($this->donation->status !== DonationStatus::Succeeded) {
            return;
        }

        $config = TrackingConfiguration::query()
            ->where('organization_id', $organization->id)
            ->where('provider', TrackingProvider::Snapchat)
            ->where('is_enabled', true)
            ->first();

        if (! $config || ! $config->isConfigured()) {
            return;
        }

        if (! $config->option('track_conversions')) {
            return;
        }

        $accessToken = $config->credential('access_token');
        $pixelId = $config->credential('pixel_id');

        if (! $accessToken || ! $pixelId) {
            return;
        }

        $event = $this->buildEvent();

        try {
            $response = Http::withToken($accessToken)
                ->timeout(30)
                ->connectTimeout(10)
                ->post('https://tr.snapchat.com/v2/conversion', [
                    'pixel_id' => $pixelId,
                    'events' => [
                        [
                            'event_type' => 'PURCHASE',
                            'event_conversion_type' => 'WEB',
                            'event_time' => $this->donation->updated_at?->getTimestamp() ?? now()->getTimestamp(),
                            'event_id' => 'purchase_'.$this->donation->public_id,
                            'hashed_email' => $event['user_data']['em'] ?? '',
                            'price' => (float) ($this->donation->base_amount ?? $this->donation->gross_amount),
                            'currency' => strtoupper($this->donation->currency),
                        ],
                    ],
                ]);

            $this->recordEvent($event, $response->successful() ? 'sent' : 'failed', $response->json() ?: ['body' => $response->body()]);
        } catch (\Exception $e) {
            $this->recordEvent($event, 'failed', ['error' => $e->getMessage()]);

            throw $e;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function buildEvent(): array
    {
        $donation = $this->donation;
        $donor = $donation->donor;
        $amount = (float) ($donation->base_amount ?? $donation->gross_amount);

        $event = [
            'event_name' => 'Purchase',
            'event_id' => 'purchase_'.$donation->public_id,
            'value' => $amount,
            'currency' => strtoupper($donation->currency),
            'user_data' => [],
        ];

        if ($donor && filled($donor->email)) {
            $event['user_data']['em'] = hash('sha256', strtolower(trim($donor->email)));
        }

        return $event;
    }

    /**
     * @param  array<string, mixed>  $event
     * @param  array<string, mixed>  $response
     */
    private function recordEvent(array $event, ?string $status, array $response): TrackingEvent
    {
        return TrackingEvent::create([
            'organization_id' => $this->donation->campaign?->organization_id,
            'donation_id' => $this->donation->id,
            'provider' => TrackingProvider::Snapchat,
            'event_name' => $event['event_name'],
            'status' => $status ?: 'failed',
            'amount' => $event['value'],
            'currency' => $event['currency'],
            'campaign_name' => $this->donation->campaign?->title,
            'payload' => $event,
            'response' => $response,
        ]);
    }
}
