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
use Illuminate\Support\Str;

class SendMetaTrackingEvent implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [10, 30, 60];

    /**
     * @param  array<string, mixed>  $userData
     * @param  array<string, mixed>  $customData
     */
    public function __construct(
        public int $organizationId,
        public string $eventName,
        public string $eventSourceUrl,
        public array $userData,
        public array $customData = [],
        public ?string $campaignName = null,
        public ?string $eventId = null,
    ) {}

    public function handle(): void
    {
        $config = TrackingConfiguration::query()
            ->where('organization_id', $this->organizationId)
            ->where('provider', TrackingProvider::Meta)
            ->where('is_enabled', true)
            ->first();

        if (! $config || ! $config->isConfigured()) {
            return;
        }

        $optionKey = match ($this->eventName) {
            'PageView' => 'track_page_views',
            'InitiateCheckout' => 'track_donation_starts',
            default => null,
        };

        if ($optionKey !== null && ! $config->option($optionKey)) {
            return;
        }

        $pixelId = $config->credential('pixel_id');
        $accessToken = $config->credential('access_token');

        if (! $pixelId || ! $accessToken) {
            return;
        }

        $event = [
            'event_name' => $this->eventName,
            'event_time' => time(),
            'event_id' => $this->eventId ?: (string) Str::uuid(),
            'action_source' => 'website',
            'event_source_url' => $this->eventSourceUrl,
            'user_data' => array_filter($this->userData),
        ];

        if ($this->customData !== []) {
            $event['custom_data'] = $this->customData;
        }

        $apiVersion = config('services.meta.api_version');
        $url = "https://graph.facebook.com/{$apiVersion}/{$pixelId}/events";

        try {
            $response = Http::withToken($accessToken)
                ->timeout(30)
                ->connectTimeout(10)
                ->post($url, [
                    'data' => [$event],
                ]);

            $this->recordEvent(
                $event,
                $response->successful() ? 'sent' : 'failed',
                $response->json() ?: ['body' => $response->body()]
            );
        } catch (\Exception $e) {
            $this->recordEvent($event, 'failed', ['error' => $e->getMessage()]);

            throw $e;
        }
    }

    /**
     * @param  array<string, mixed>  $event
     * @param  array<string, mixed>  $response
     */
    private function recordEvent(array $event, ?string $status, array $response): TrackingEvent
    {
        return TrackingEvent::create([
            'organization_id' => $this->organizationId,
            'provider' => TrackingProvider::Meta,
            'event_name' => $event['event_name'],
            'status' => $status ?: 'failed',
            'amount' => $event['custom_data']['value'] ?? null,
            'currency' => $event['custom_data']['currency'] ?? null,
            'campaign_name' => $this->campaignName,
            'payload' => $event,
            'response' => $response,
        ]);
    }
}
