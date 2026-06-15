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

    public string $sortField = 'created_at';

    public string $sortDirection = 'desc';

    public bool $showCreateModal = false;

    #[Validate('required|string|in:button,floating_button,form,popup,link,sticky_button,qr_code')]
    public string $newType = 'button';

    #[Validate('required|string|max:255')]
    public string $newName = '';

    #[Validate('required|exists:campaigns,id')]
    public ?int $newCampaignId = null;

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
                'button_size' => 'text-base px-6 py-3',
                'corner_radius' => 8,
                'button_icon' => 'heart',
                'button_effect' => 'none',
            ],
            'sticky_button' => [
                'button_text' => 'Donate',
                'button_color' => 'bg-blue-600 hover:bg-blue-700',
                'button_size' => 'text-base px-6 py-3',
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
            'link' => [
                'button_text' => 'Donate',
                'text' => 'Donate',
                'icon' => 'heart',
                'alignment' => 'center',
                'action' => 'checkout_modal',
            ],
            'qr_code' => [
                'label' => 'Scan to donate',
                'size' => 'medium',
                'alignment' => 'center',
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
            ->with('campaign');

        if (filled($this->search)) {
            $query->where('name', 'like', '%'.$this->search.'%');
        }

        if (filled($this->typeFilter)) {
            $query->where('type', $this->typeFilter);
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

    public function render()
    {
        return view('livewire.app.elements.index', [
            'title' => 'Elements',
        ]);
    }
}
