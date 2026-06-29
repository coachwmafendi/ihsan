<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\WebhookLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $gateway
 * @property string $stripe_event_id
 * @property string $event_type
 * @property array<array-key, mixed> $payload
 * @property string $status
 * @property string|null $error_message
 * @property CarbonImmutable|null $processed_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 *
 * @method static \Database\Factories\WebhookLogFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebhookLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebhookLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebhookLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebhookLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebhookLog whereErrorMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebhookLog whereEventType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebhookLog whereGateway($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebhookLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebhookLog wherePayload($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebhookLog whereProcessedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebhookLog whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebhookLog whereStripeEventId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebhookLog whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
#[Fillable(['gateway', 'stripe_event_id', 'event_type', 'payload', 'status', 'error_message', 'processed_at'])]
class WebhookLog extends Model
{
    /** @use HasFactory<WebhookLogFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'processed_at' => 'datetime',
        ];
    }
}
