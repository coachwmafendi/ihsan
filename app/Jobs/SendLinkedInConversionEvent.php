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

class SendLinkedInConversionEvent implements ShouldQueue
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
            ->where('provider', TrackingProvider::LinkedIn)
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

        $conversionId = $config->credential('conversion_id');

        if (! $conversionId) {
            return;
        }

        $event = $this->buildEvent();

        try {
            $response = Http::withToken($accessToken)
                ->withHeaders(['LinkedIn-Version' => '202405'])
                ->timeout(30)
                ->connectTimeout(10)
                ->post('https://api.linkedin.com/rest/conversions', [
                    'conversion' => 'urn:lla:llaPartnerConversion:'.$conversionId,
                    'conversionHappenedAt' => ($this->donation->updated_at?->getTimestamp() ?? now()->getTimestamp()) * 1000,
                    'conversionValue' => [
                        'currencyCode' => strtoupper($this->donation->currency),
                        'amount' => (string) ($this->donation->base_amount ?? $this->donation->gross_amount),
                    ],
                    'eventId' => 'purchase_'.$this->donation->public_id,
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
        $amount = (float) ($donation->base_amount ?? $donation->gross_amount);

        return [
            'event_name' => 'Purchase',
            'event_id' => 'purchase_'.$donation->public_id,
            'value' => $amount,
            'currency' => strtoupper($donation->currency),
        ];
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
            'provider' => TrackingProvider::LinkedIn,
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
