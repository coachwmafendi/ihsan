<?php

namespace App\Jobs;

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

class SendXAdsConversionEvent implements ShouldQueue
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

        $config = TrackingConfiguration::query()
            ->where('organization_id', $organization->id)
            ->where('provider', TrackingProvider::XAds)
            ->where('is_enabled', true)
            ->first();

        if (! $config || ! $config->isConfigured()) {
            return;
        }

        if (! $config->option('track_conversions')) {
            return;
        }

        $accessToken = $config->credential('access_token');

        if (! $accessToken) {
            return;
        }

        $event = $this->buildEvent();
        $conversionId = $config->credential('conversion_id');

        if (! $conversionId) {
            return;
        }

        try {
            $response = Http::withToken($accessToken, 'Bearer')
                ->timeout(30)
                ->connectTimeout(10)
                ->post('https://ads-api.twitter.com/12/measurement/conversions/events', [
                    'conversions' => [
                        [
                            'conversion_time' => $this->donation->updated_at?->toIso8601String() ?? now()->toIso8601String(),
                            'event_id' => 'purchase_'.$this->donation->public_id,
                            'identifiers' => [
                                ['hashed_email' => $event['user_data']['em'] ?? ''],
                            ],
                            'conversion_metadata' => [
                                'currency' => strtoupper($this->donation->currency),
                                'value' => (string) ($this->donation->base_amount ?? $this->donation->gross_amount),
                            ],
                            'conversion_id' => $conversionId,
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
            'provider' => TrackingProvider::XAds,
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
