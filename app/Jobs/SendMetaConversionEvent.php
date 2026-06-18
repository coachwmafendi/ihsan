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

class SendMetaConversionEvent implements ShouldQueue
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
        $campaign = $this->donation->campaign;
        $organization = $campaign?->organization;

        if (! $organization) {
            return;
        }

        $config = TrackingConfiguration::query()
            ->where('organization_id', $organization->id)
            ->where('provider', TrackingProvider::Meta)
            ->where('is_enabled', true)
            ->first();

        if (! $config || ! $config->isConfigured()) {
            return;
        }

        if (! $config->option('track_successful_donations')) {
            return;
        }

        $pixelId = $config->credential('pixel_id');
        $accessToken = $config->credential('access_token');

        if (! $pixelId) {
            return;
        }

        $event = $this->buildEvent();

        if (! $accessToken) {
            return;
        }

        $url = "https://graph.facebook.com/v18.0/{$pixelId}/events";

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
     * @return array<string, mixed>
     */
    private function buildEvent(): array
    {
        $donation = $this->donation;
        $campaign = $donation->campaign;
        $donor = $donation->donor;
        $utmParams = $donation->utm_params ?? [];

        $eventTime = $donation->updated_at?->timestamp ?? now()->timestamp;
        $amount = (float) ($donation->base_amount ?? $donation->gross_amount);
        $currency = strtoupper($donation->currency);
        $eventId = 'purchase_'.$donation->public_id;

        $userData = [
            'client_ip_address' => $donation->ip_address,
            'client_user_agent' => $donation->browser,
        ];

        if ($donor && filled($donor->email)) {
            $userData['em'] = hash('sha256', strtolower(trim($donor->email)));
        }

        if (filled($utmParams['fbclid'] ?? null)) {
            $clickId = $utmParams['fbclid'];
            $timestamp = (string) ($donation->created_at?->timestamp ?? $eventTime);
            $userData['fbc'] = 'fb.1.'.$timestamp.'.'.$clickId;
        }

        $customData = [
            'value' => $amount,
            'currency' => $currency,
            'content_type' => 'product',
            'contents' => [[
                'id' => (string) ($campaign?->public_id ?? 'campaign'),
                'quantity' => 1,
                'item_price' => $amount,
            ]],
        ];

        return array_filter([
            'event_name' => 'Purchase',
            'event_time' => $eventTime,
            'event_id' => $eventId,
            'event_source_url' => $donation->page_url,
            'action_source' => 'website',
            'user_data' => array_filter($userData),
            'custom_data' => $customData,
        ]);
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
            'provider' => TrackingProvider::Meta,
            'event_name' => $event['event_name'],
            'status' => $status ?: 'failed',
            'amount' => $event['custom_data']['value'] ?? 0,
            'currency' => $event['custom_data']['currency'] ?? null,
            'campaign_name' => $this->donation->campaign?->title,
            'payload' => $event,
            'response' => $response,
        ]);
    }
}
