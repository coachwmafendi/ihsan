<?php

declare(strict_types=1);

namespace App\Livewire\App\AuditLog;

use App\Models\Organization;
use App\Services\AuditLogQuery;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

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
}
