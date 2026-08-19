<?php

declare(strict_types=1);

namespace App\Livewire\App\AuditLog;

use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\Subscription;
use App\Models\User;
use App\Services\AuditLogQuery;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;

#[Layout('layouts.app')]
#[Title('Audit Log')]
class Index extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $eventFilter = '';

    #[Url(except: '')]
    public string $subjectTypeFilter = '';

    #[Url(except: 'all_time')]
    public string $period = 'all_time';

    #[Url(except: 'all')]
    public string $initiatorFilter = 'all';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedEventFilter(): void
    {
        $this->resetPage();
    }

    public function updatedSubjectTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPeriod(): void
    {
        $this->resetPage();
    }

    public function updatedInitiatorFilter(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->eventFilter = '';
        $this->subjectTypeFilter = '';
        $this->period = 'all_time';
        $this->initiatorFilter = 'all';
        $this->resetPage();
    }

    #[Computed]
    public function organization(): ?Organization
    {
        return Auth::user()?->organization;
    }

    #[Computed]
    public function hasActiveFilters(): bool
    {
        return $this->search !== ''
            || $this->eventFilter !== ''
            || $this->subjectTypeFilter !== ''
            || $this->period !== 'all_time'
            || $this->initiatorFilter !== 'all';
    }

    #[Computed]
    public function activities()
    {
        $organization = $this->organization;

        if (! $organization instanceof Organization) {
            return new LengthAwarePaginator([], 0, 25);
        }

        return AuditLogQuery::forOrganization($organization, [
            'search' => $this->search,
            'event' => $this->eventFilter,
            'subject_type' => $this->subjectTypeFilter,
            'period' => $this->period,
            'initiator' => $this->initiatorFilter,
        ])->paginate(25);
    }

    public function render()
    {
        return view('livewire.app.audit-log.index', [
            'eventOptions' => AuditLogQuery::eventOptions(),
            'subjectTypeOptions' => AuditLogQuery::subjectTypeOptions(),
            'initiatorOptions' => AuditLogQuery::initiatorOptions(),
        ]);
    }

    public function initiatorName(Activity $activity): string
    {
        $initiator = $activity->properties?->get('initiator');

        if ($initiator === 'donor') {
            return 'Supporter';
        }

        if ($activity->causer instanceof User) {
            return $activity->causer->name;
        }

        if ($initiator === 'admin') {
            return 'Admin';
        }

        return 'System';
    }

    /**
     * Where the event came from, or null when it was never recorded.
     *
     * Most entries carry no source at all, so claiming they were automated
     * would put words in the log's mouth — an admin's own edit would read as
     * something the system did.
     */
    public function eventSource(Activity $activity): ?string
    {
        $source = $activity->properties?->get('source');

        return match ($source) {
            'manual' => 'Manual',
            'webhook' => 'Webhook',
            'system_automated' => 'System (automated)',
            'donor_portal' => 'Donor portal',
            'virtual_terminal' => 'Virtual terminal',
            'checkout_modal' => 'Checkout modal',
            'campaign_page' => 'Campaign page',
            'element' => 'Embedded element',
            default => null,
        };
    }

    /**
     * @return array{from: string|null, to: string|null}|null
     */
    public function statusTransition(Activity $activity): ?array
    {
        $properties = $activity->properties ?? collect();

        $to = $properties->get('to_status');
        $from = $properties->get('from_status');

        if ($to === null && $from === null) {
            return null;
        }

        return [
            'from' => $from,
            'to' => $to,
        ];
    }

    public function subjectUrl(Activity $activity): ?string
    {
        $subject = $activity->subject;

        if ($subject instanceof Donation) {
            return route('app.donations.show', $subject);
        }

        if ($subject instanceof Subscription) {
            return route('app.subscriptions.show', $subject);
        }

        if ($subject instanceof Campaign) {
            return route('app.campaigns.edit', $subject);
        }

        if ($subject instanceof Donor) {
            return route('app.supporters.show', $subject);
        }

        return null;
    }

    public function statusColor(string $status): string
    {
        return match ($status) {
            'succeeded', 'active', 'paid' => 'success',
            'pending', 'incomplete' => 'warning',
            'failed', 'cancelled', 'refunded' => 'danger',
            default => 'default',
        };
    }

    /**
     * Colour for the event badge on a collapsed row.
     *
     * The status a row moved to is the strongest signal; failing that, the
     * event name itself says whether something was created or went wrong.
     */
    public function eventColor(Activity $activity): string
    {
        $toStatus = $activity->properties?->get('to_status');

        if (is_string($toStatus) && $toStatus !== '') {
            return $this->statusColor($toStatus);
        }

        $event = (string) ($activity->event ?? '');

        return match (true) {
            str_contains($event, 'failed'), str_contains($event, 'cancelled'),
            str_contains($event, 'refunded'), str_contains($event, 'disconnected'),
            str_contains($event, 'deleted') => 'danger',
            str_contains($event, 'succeeded'), str_contains($event, 'charged'),
            str_contains($event, 'connected'), str_contains($event, 'completed') => 'success',
            str_contains($event, 'paused'), str_contains($event, 'initiated') => 'warning',
            default => 'default',
        };
    }

    /**
     * A human label for an event name such as "donation.succeeded".
     */
    public function eventLabel(Activity $activity): string
    {
        $event = (string) ($activity->event ?? '');

        if ($event === '') {
            return 'Unknown';
        }

        return AuditLogQuery::eventOptions()[$event]
            ?? Str::of($event)->replace(['.', '_'], ' ')->title()->toString();
    }

    /**
     * What to call the record an entry is about.
     *
     * Donors are called supporters everywhere a person can see, so the raw
     * class name would be the only place the old word leaks through.
     */
    public function subjectTypeLabel(Activity $activity): string
    {
        $type = (string) ($activity->subject_type ?? '');

        return AuditLogQuery::subjectTypeOptions()[$type] ?? class_basename($type);
    }

    /**
     * @return array<int, array{field: string, old: string, new: string}>
     */
    public function changedAttributes(Activity $activity): array
    {
        $properties = $activity->properties ?? collect();

        $attributes = $properties->get('attributes', []);
        $old = $properties->get('old', []);

        if (! is_array($attributes)) {
            return [];
        }

        $changes = [];

        foreach ($attributes as $key => $newValue) {
            $oldValue = is_array($old) ? ($old[$key] ?? null) : null;

            $changes[] = [
                'field' => (string) $key,
                'old' => $this->formatValue($oldValue),
                'new' => $this->formatValue($newValue),
            ];
        }

        return $changes;
    }

    private function formatValue(mixed $value): string
    {
        if ($value === null) {
            return '—';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_array($value) || is_object($value)) {
            return json_encode($value) ?: '—';
        }

        return (string) $value;
    }
}
