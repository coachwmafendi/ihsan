<?php

declare(strict_types=1);

namespace App\Livewire\App\AuditLog;

use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Organization;
use App\Models\Subscription;
use App\Models\User;
use App\Services\AuditLogQuery;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
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

    public function actorName(Activity $activity): string
    {
        if ($activity->causer instanceof User) {
            return $activity->causer->name;
        }

        return $activity->causer_type ? 'System user' : 'System';
    }

    public function initiatorName(Activity $activity): string
    {
        $initiator = $activity->properties?->get('initiator');

        if ($initiator === 'donor') {
            return 'Donor';
        }

        if ($activity->causer instanceof User) {
            return $activity->causer->name;
        }

        if ($initiator === 'admin') {
            return 'Admin';
        }

        return 'System';
    }

    public function eventSource(Activity $activity): string
    {
        $source = $activity->properties?->get('source');

        return match ($source) {
            'manual' => 'Manual',
            'webhook' => 'Webhook',
            'donor_portal' => 'Donor portal',
            'system_automated' => 'System (automated)',
            default => 'System (automated)',
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

    public function subjectLabel(Activity $activity): string
    {
        $subject = $activity->subject;

        if ($subject === null) {
            return (string) ($activity->subject_type ? class_basename($activity->subject_type) : '—');
        }

        $name = $subject->name
            ?? $subject->title
            ?? $subject->email
            ?? $subject->public_id
            ?? ('#'.$subject->getKey());

        return class_basename($subject).' — '.(string) $name;
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
