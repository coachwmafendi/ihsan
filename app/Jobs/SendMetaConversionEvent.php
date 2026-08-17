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
     * @return array<string, mixed>
     */
    private function buildEvent(): array
    {
        $donation = $this->donation;
        $campaign = $donation->campaign;
        $donor = $donation->donor;
        $utmParams = $donation->utm_params ?? [];

        $eventTime = $donation->updated_at?->timestamp ?? now()->timestamp;
        // Value and currency must describe the same money — base_amount is
        // stored in base_currency, not in the donation's charge currency.
        $useBase = $donation->base_amount !== null && filled($donation->base_currency);
        $amount = (float) ($useBase ? $donation->base_amount : $donation->gross_amount);
        $currency = strtoupper((string) ($useBase ? $donation->base_currency : $donation->currency));
        $eventId = 'purchase_'.$donation->public_id;

        $userData = [
            'client_ip_address' => $donation->ip_address,
            'client_user_agent' => $donation->user_agent,
            'fbp' => $donation->fbp,
            'fbc' => $donation->fbc,
        ];

        if ($donor && filled($donor->email)) {
            $userData['em'] = self::hashValue(strtolower(trim($donor->email)));
            $userData['external_id'] = self::hashValue(strtolower(trim($donor->email)));
        }

        if ($donor && filled($donor->phone)) {
            $phone = preg_replace('/\D/', '', (string) $donor->phone) ?? '';

            if ($phone !== '') {
                $userData['ph'] = self::hashValue($phone);
            }
        }

        if ($donor && filled($donor->first_name)) {
            $userData['fn'] = self::hashValue(self::normalizeName($donor->first_name));
        }

        if ($donor && filled($donor->last_name)) {
            $userData['ln'] = self::hashValue(self::normalizeName($donor->last_name));
        }

        $country = $donation->billing_country ?? $donation->donor_country;

        if (filled($country)) {
            $userData['country'] = self::hashValue(strtolower(trim((string) $country)));
        }

        if (filled($donation->billing_address_city)) {
            $userData['ct'] = self::hashValue(self::normalizeName($donation->billing_address_city));
        }

        if (filled($donation->billing_address_postal_code)) {
            $userData['zp'] = self::hashValue(strtolower(trim((string) $donation->billing_address_postal_code)));
        }

        if (blank($userData['fbc']) && filled($utmParams['fbclid'] ?? null)) {
            $clickId = $utmParams['fbclid'];
            $timestamp = (string) (($donation->created_at?->timestamp ?? $eventTime) * 1000);
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
            'event_source_url' => $donation->page_url ?: config('app.url'),
            'action_source' => 'website',
            'user_data' => array_filter($userData),
            'custom_data' => $customData,
        ]);
    }

    private static function hashValue(string $value): string
    {
        return hash('sha256', $value);
    }

    /**
     * Meta expects names lowercased with punctuation and whitespace removed.
     */
    private static function normalizeName(string $value): string
    {
        return preg_replace('/[^a-z0-9]/', '', strtolower(trim($value))) ?? '';
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
