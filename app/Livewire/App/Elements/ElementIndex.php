<?php

declare(strict_types=1);

namespace App\Livewire\App\Elements;

use App\Enums\ElementType;
use App\Models\Campaign;
use App\Models\Element;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class ElementIndex extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $typeFilter = '';

    #[Url(except: '')]
    public string $statusFilter = '';

    public string $sortField = 'created_at';

    public string $sortDirection = 'desc';

    public bool $showCreateModal = false;

    public bool $showArchived = false;

    #[Validate('required|string|in:button,floating_button,sticky_button,form,popup,qr_code,link')]
    public string $newType = 'button';

    #[Validate('required|string|max:255')]
    public string $newName = '';

    #[Validate('required|exists:campaigns,id')]
    public ?int $newCampaignId = null;

    public function toggleArchived(): void
    {
        $this->showArchived = ! $this->showArchived;
        $this->resetPage();
    }

    public function unarchive(int $elementId): void
    {
        $element = Element::findOrFail($elementId);
        $this->authorize('unarchive', $element);
        $element->unarchive();
        $this->dispatch('notify', message: 'Element restored successfully.', variant: 'success');
    }

    public function edit(int $elementId): void
    {
        $element = Element::findOrFail($elementId);
        $this->authorize('update', $element);

        $this->redirectRoute('app.elements.edit', $element, navigate: true);
    }

    public function archive(int $elementId): void
    {
        $element = Element::findOrFail($elementId);
        $this->authorize('archive', $element);
        $element->archive();
        $this->dispatch('notify', message: 'Element archived successfully.', variant: 'success');
    }

    public function duplicate(int $elementId): void
    {
        $element = Element::findOrFail($elementId);
        $this->authorize('create', Element::class);

        $org = $this->organization;

        if (! $org || $element->organization_id !== $org->id) {
            abort(403);
        }

        $copy = $element->replicate([
            'token',
            'public_id',
            'form_slug',
        ]);

        $copy->name = $element->name.' (Copy)';
        $copy->token = $this->generateUniqueToken();
        $copy->public_id = null;
        $copy->is_active = true;
        $copy->archived_at = null;
        $copy->is_donor_portal_default = false;
        $copy->save();

        $this->dispatch('notify', message: 'Element duplicated successfully.', variant: 'success');

        $this->redirectRoute('app.elements.edit', $copy);
    }

    public function openCreateModal(): void
    {
        $this->newType = 'button';
        $this->newName = '';
        $this->newCampaignId = null;
        $this->resetValidation();
        $this->showCreateModal = true;
    }

    public function createElement(): void
    {
        $this->validate([
            'newType' => 'required|string',
            'newName' => 'required|string|max:255',
            'newCampaignId' => 'required|exists:campaigns,id',
        ]);

        $org = $this->organization;

        if (! $org) {
            return;
        }

        $campaign = Campaign::find($this->newCampaignId);

        if (! $campaign || $campaign->organization_id !== $org->id) {
            abort(403);
        }

        $token = $this->generateUniqueToken();
        $type = ElementType::from($this->newType);

        $config = match ($type->value) {
            'button' => [
                'button_text' => 'Donate',
                'button_color' => 'bg-blue-600 hover:bg-blue-700',
                'button_size' => 'medium',
                'corner_radius' => 8,
                'button_icon' => 'heart',
                'button_effect' => 'none',
            ],
            'sticky_button' => [
                'button_text' => 'Donate',
                'button_color' => 'bg-blue-600 hover:bg-blue-700',
                'button_size' => 'medium',
                'corner_radius' => 8,
                'button_icon' => 'heart',
                'button_effect' => 'none',
                'position' => 'right-center',
            ],
            'floating_button' => [
                'button_text' => 'Donate',
                'icon' => 'heart',
            ],
            'form' => [
                'title' => 'Support our cause',
                'message' => "Let's seize the opportunity to contribute through waqf today. May Allah accept our waqf, expand our sustenance, bless our families, and make it a source of continuous reward that flows until the Hereafter.\n\nGive waqf today, reap endless rewards forever.",
                'button_text' => 'Donate Now',
                'submit_text' => 'Donate Now',
                'action' => 'checkout_modal',
                'color' => 'campaign',
            ],
            'qr_code' => [
                'title' => 'Scan to Donate',
                'message' => '',
                'color' => 'campaign',
            ],
            'link' => [
                'button_text' => 'Donate',
                'text' => 'Donate',
                'icon' => 'heart',
                'alignment' => 'center',
                'action' => 'checkout_modal',
            ],
            'popup' => [
                'title' => 'Support our cause',
                'message' => 'Give waqf today. May Allah accept our waqf and bless our families with endless rewards.',
                'button_text' => 'Donate Now',
                'action' => 'checkout_modal',
                'trigger' => 'after_delay',
                'delay' => 8,
                'frequency' => 'once_per_day',
                'visibility' => 'desktop_mobile',
                'layout' => 'simple',
                'image_url' => 'https://images.unsplash.com/photo-1629273229664-11fabc0becc0?q=80&w=2062',
                'color' => 'campaign',
                'button_effect' => 'none',
            ],
            default => null,
        };

        $element = Element::create([
            'organization_id' => $org->id,
            'campaign_id' => $this->newCampaignId,
            'type' => $type,
            'name' => $this->newName,
            'token' => $token,
            'is_active' => true,
            'config' => $config,
        ]);

        $this->showCreateModal = false;
        $this->dispatch('notify', message: 'Element created successfully.', variant: 'success');

        $this->redirectRoute('app.elements.edit', $element);
    }

    private function generateUniqueToken(): string
    {
        $attempts = 0;

        do {
            $token = Str::random(6);
            $exists = Element::where('token', $token)->exists();
            $attempts++;
        } while ($exists && $attempts < 10);

        return $token;
    }

    #[Computed]
    public function campaigns()
    {
        $org = $this->organization;

        if (! $org) {
            return collect();
        }

        return Campaign::query()
            ->where('organization_id', $org->id)
            ->orderBy('title')
            ->get();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    #[Computed]
    public function organization()
    {
        return Auth::user()?->organization;
    }

    private function baseQuery(): Builder
    {
        $org = $this->organization;

        $query = Element::query()
            ->when($org, fn (Builder $q) => $q->where('elements.organization_id', $org->id))
            ->when(! $org, fn (Builder $q) => $q->whereRaw('1 = 0'))
            ->when(
                $this->showArchived,
                fn (Builder $q) => $q->whereNotNull('elements.archived_at'),
                fn (Builder $q) => $q->whereNull('elements.archived_at'),
            )
            ->with('campaign');

        if (filled($this->search)) {
            $query->whereRaw('LOWER(name) LIKE ?', ['%'.strtolower($this->search).'%']);
        }

        if (filled($this->typeFilter)) {
            $query->where('type', $this->typeFilter);
        }

        if (filled($this->statusFilter)) {
            $query->where('is_active', $this->statusFilter === 'active');
        }

        return $query;
    }

    #[Computed]
    public function elements()
    {
        $query = $this->baseQuery();

        $allowedSorts = ['name', 'type', 'campaign', 'is_active', 'created_at'];
        $field = in_array($this->sortField, $allowedSorts, true) ? $this->sortField : 'created_at';
        $direction = in_array($this->sortDirection, ['asc', 'desc'], true) ? $this->sortDirection : 'desc';

        if ($field === 'campaign') {
            $query->leftJoin('campaigns', 'campaigns.id', '=', 'elements.campaign_id')
                ->orderBy('campaigns.title', $direction)
                ->select('elements.*');
        } else {
            $query->orderBy($field, $direction);
        }

        return $query->paginate(25);
    }

    #[Computed]
    public function totalCount(): int
    {
        return $this->baseQuery()->count();
    }

    #[Computed]
    public function archivedCount(): int
    {
        $org = $this->organization;

        return Element::query()
            ->when($org, fn (Builder $q) => $q->where('elements.organization_id', $org->id))
            ->when(! $org, fn (Builder $q) => $q->whereRaw('1 = 0'))
            ->whereNotNull('archived_at')
            ->count();
    }

    public function render()
    {
        return view('livewire.app.elements.index', [
            'title' => 'Elements',
        ]);
    }
}
