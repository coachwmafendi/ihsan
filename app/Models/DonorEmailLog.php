<?php

namespace App\Models;

use App\Mail\DonationReceipt;
use App\Mail\DonorDunningNotification;
use App\Mail\DonorNewSubscriptionNotification;
use App\Mail\DonorPaymentMethodChangedNotification;
use App\Mail\DonorPaymentMethodExpiringNotification;
use App\Mail\DonorRecurringPaymentNotification;
use App\Mail\DonorRefundNotification;
use App\Mail\DonorSubscriptionCancelledNotification;
use App\Mail\DonorSubscriptionFailedNotification;
use App\Mail\FailedDonationRecovery;
use App\Mail\FailedPaymentNotification;
use App\Mail\MagicLink;
use App\Mail\SupporterSubscriptionAmountChangedNotification;
use App\Services\PublicIdGenerator;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Database\Factories\DonorEmailLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $donor_id
 * @property int|null $organization_id
 * @property int|null $donation_id
 * @property int|null $subscription_id
 * @property int|null $resent_from_id
 * @property string $mailable_class
 * @property string $subject
 * @property array<array-key, mixed>|null $metadata
 * @property CarbonImmutable|null $sent_at
 * @property CarbonImmutable|null $opened_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property string|null $public_id
 * @property-read Donor $donor
 * @property-read Organization|null $organization
 * @property-read Donation|null $donation
 * @property-read Subscription|null $subscription
 * @property-read DonorEmailLog|null $originalLog
 * @property-read Collection<int, DonorEmailLog> $resends
 * @property-read string $status_label
 * @property-read string $status_tone
 * @property-read CarbonInterface|null $status_at
 * @property-read string|null $status_detail
 * @property-read string $type_label
 * @property-read string $short_subject
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DonorEmailLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DonorEmailLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DonorEmailLog query()
 *
 * @mixin \Eloquent
 */
#[Fillable([
    'donor_id', 'organization_id', 'donation_id', 'subscription_id', 'resent_from_id',
    'mailable_class', 'message_id', 'provider_message_id', 'subject', 'delivery_status',
    'metadata', 'sent_at', 'opened_at', 'delivered_at', 'bounced_at', 'bounce_reason', 'complained_at',
])]
class DonorEmailLog extends Model
{
    /** @use HasFactory<DonorEmailLogFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'sent_at' => 'datetime',
            'opened_at' => 'datetime',
            'delivered_at' => 'datetime',
            'bounced_at' => 'datetime',
            'complained_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (DonorEmailLog $log) {
            if (! $log->public_id) {
                $log->public_id = PublicIdGenerator::generate(static::class);
            }
        });
    }

    public function donor(): BelongsTo
    {
        return $this->belongsTo(Donor::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function donation(): BelongsTo
    {
        return $this->belongsTo(Donation::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function originalLog(): BelongsTo
    {
        return $this->belongsTo(self::class, 'resent_from_id');
    }

    public function resends(): HasMany
    {
        return $this->hasMany(self::class, 'resent_from_id');
    }

    /**
     * What actually became of this email, strongest signal first.
     *
     * A bounce and a delivered-but-unread email both used to render as a dash,
     * and those two need opposite action from staff.
     */
    public function statusLabel(): Attribute
    {
        return Attribute::get(fn (): string => match (true) {
            $this->bounced_at !== null || $this->delivery_status === 'bounced' => 'Bounced',
            $this->complained_at !== null || $this->delivery_status === 'complained' => 'Marked spam',
            $this->opened_at !== null => 'Opened',
            $this->delivered_at !== null || $this->delivery_status === 'delivered' => 'Delivered',
            $this->sent_at !== null || $this->delivery_status === 'sent' => 'Sent',
            default => 'Queued',
        });
    }

    /**
     * The x-ui.badge status that matches this email's outcome.
     */
    public function statusTone(): Attribute
    {
        return Attribute::get(fn (): string => match ($this->status_label) {
            'Bounced' => 'failed',
            'Marked spam' => 'warning',
            'Opened' => 'success',
            'Delivered' => 'info',
            'Sent' => 'default',
            default => 'pending',
        });
    }

    /**
     * The moment the status happened.
     */
    public function statusAt(): Attribute
    {
        return Attribute::get(fn (): ?CarbonInterface => match ($this->status_label) {
            'Bounced' => $this->bounced_at,
            'Marked spam' => $this->complained_at,
            'Opened' => $this->opened_at,
            'Delivered' => $this->delivered_at,
            'Sent' => $this->sent_at,
            default => null,
        });
    }

    /**
     * The sentence explaining the status, for a tooltip. Null when the label says enough.
     */
    public function statusDetail(): Attribute
    {
        return Attribute::get(fn (): ?string => match ($this->status_label) {
            'Bounced' => $this->bounce_reason ?: 'The mail server rejected this address.',
            'Marked spam' => 'The supporter reported this email as spam.',
            'Sent' => 'Handed to the mail server. No delivery confirmation yet.',
            'Queued' => 'Waiting to go out.',
            default => null,
        });
    }

    /**
     * A short name for the kind of email this is.
     *
     * The subject repeats the organisation on a page already scoped to it, so the
     * kind of email is what makes the list scannable.
     */
    public function typeLabel(): Attribute
    {
        return Attribute::get(fn (): string => match ($this->mailable_class) {
            DonationReceipt::class => 'Receipt',
            DonorRefundNotification::class => 'Refund',
            DonorNewSubscriptionNotification::class => 'New plan',
            DonorRecurringPaymentNotification::class => 'Installment',
            DonorSubscriptionCancelledNotification::class => 'Plan cancelled',
            SupporterSubscriptionAmountChangedNotification::class => 'Plan changed',
            DonorPaymentMethodChangedNotification::class => 'Card changed',
            DonorPaymentMethodExpiringNotification::class => 'Card expiring',
            FailedPaymentNotification::class,
            DonorSubscriptionFailedNotification::class,
            FailedDonationRecovery::class,
            DonorDunningNotification::class => 'Payment failed',
            MagicLink::class => 'Portal link',
            default => 'Email',
        });
    }

    /**
     * The subject with the organisation's own name taken out.
     */
    public function shortSubject(): Attribute
    {
        return Attribute::get(function (): string {
            $organizationName = $this->organization?->name;

            if (blank($organizationName)) {
                return $this->subject;
            }

            $stripped = str_replace($organizationName, '', $this->subject);
            $stripped = trim($stripped);
            $stripped = trim($stripped, "—-–|· \t\n\r\0\x0B");

            return Str::squish($stripped) ?: $this->subject;
        });
    }
}
