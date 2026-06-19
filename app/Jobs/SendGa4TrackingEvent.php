<?php

namespace App\Jobs;

use App\Enums\TrackingProvider;
use App\Models\TrackingConfiguration;
use App\Models\TrackingEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class SendGa4TrackingEvent implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [10, 30, 60];

    /**
     * @param  array<string, mixed>  $eventParams
     */
    public function __construct(
        public int $organizationId,
        public string $eventName,
        public string $clientId,
        public string $eventSourceUrl,
        public array $eventParams = [],
        public ?string $campaignName = null,
    ) {}

    public function handle(): void
    {
        $config = TrackingConfiguration::query()
            ->where('organization_id', $this->organizationId)
            ->where('provider', TrackingProvider::GoogleAnalytics4)
            ->where('is_enabled', true)
            ->first();

        if (! $config || ! $config->isConfigured()) {
            return;
        }

        $optionKey = match ($this->eventName) {
            'page_view' => 'track_page_views',
            'begin_checkout' => 'track_checkout_starts',
            default => null,
        };

        if ($optionKey !== null && ! $config->option($optionKey)) {
            return;
        }

        $measurementId = $config->credential('measurement_id');
        $apiSecret = $config->credential('api_secret');

        if (! $measurementId || ! $apiSecret) {
            return;
        }

        $payload = [
            'client_id' => $this->clientId,
            'events' => [[
                'name' => $this->eventName,
                'params' => array_merge(
                    $this->eventParams,
                    ['page_location' => $this->eventSourceUrl]
                ),
            ]],
        ];

        $url = "https://www.google-analytics.com/mp/collect?measurement_id={$measurementId}&api_secret={$apiSecret}";

        try {
            $response = Http::timeout(30)
                ->connectTimeout(10)
                ->post($url, $payload);

            $this->recordEvent(
                $payload,
                $response->successful() ? 'sent' : 'failed',
                $response->body() !== '' ? ($response->json() ?: ['body' => $response->body()]) : ['body' => '']
            );
        } catch (\Exception $e) {
            $this->recordEvent($payload, 'failed', ['error' => $e->getMessage()]);

            throw $e;
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $response
     */
    private function recordEvent(array $payload, ?string $status, array $response): TrackingEvent
    {
        $event = $payload['events'][0] ?? [];
        $params = $event['params'] ?? [];

        return TrackingEvent::create([
            'organization_id' => $this->organizationId,
            'provider' => TrackingProvider::GoogleAnalytics4,
            'event_name' => $event['name'] ?? $this->eventName,
            'status' => $status ?: 'failed',
            'amount' => $params['value'] ?? null,
            'currency' => $params['currency'] ?? null,
            'campaign_name' => $this->campaignName,
            'payload' => $payload,
            'response' => $response,
        ]);
    }
}
