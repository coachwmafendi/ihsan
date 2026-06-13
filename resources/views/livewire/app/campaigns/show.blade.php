{{-- resources/views/livewire/app/campaigns/show.blade.php --}}
<div class="space-y-6">
    {{-- Page Header --}}
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="flex items-center gap-4">
            @if ($campaign->image_path)
                <img src="{{ Storage::disk('public')->url($campaign->image_path) }}" alt="" class="h-14 w-14 rounded-xl object-cover" />
            @else
                <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-teal-50">
                    <x-heroicon-o-megaphone class="size-6 text-teal-600" />
                </div>
            @endif
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-3xl font-bold tracking-tight text-slate-900">{{ $campaign->title }}</h1>
                    <x-ui.badge status="{{ $campaign->status->value }}" size="md">
                        {{ ucfirst($campaign->status->value) }}
                    </x-ui.badge>
                </div>
                <p class="mt-1 text-sm text-slate-500">
                    Created {{ $campaign->created_at->format('M d, Y') }}
                    @if ($campaign->has_end_date && $campaign->end_date)
                        <span class="text-slate-300">·</span> Ends {{ $campaign->end_date->format('M d, Y') }}
                    @endif
                </p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <x-ui.button href="{{ route('app.campaigns.edit', $campaign) }}" variant="secondary">
                <x-heroicon-o-pencil class="size-4" />
                Edit
            </x-ui.button>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-ui.stat-card
            label="Amount Raised"
            value="{{ $this->amountRaised }}"
            subtext="{{ $campaign->has_target && $campaign->target_amount ? 'Goal: RM '.number_format((float) $campaign->target_amount, 2) : 'No target set' }}"
        />
        <x-ui.stat-card
            label="Donations"
            value="{{ number_format($this->donationsCount) }}"
        />
        <x-ui.stat-card
            label="Active Recurring"
            value="{{ number_format($this->activeRecurringCount) }}"
        />
        <x-ui.stat-card
            label="Last Donation"
            value="{{ $this->lastDonation ? $this->lastDonation->created_at->diffForHumans() : '—' }}"
            subtext="{{ $this->lastDonation ? ($this->lastDonation->donor?->full_name ?? 'Anonymous') : '' }}"
        />
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Left Column --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- About --}}
            @if ($campaign->description)
                <x-ui.card title="About">
                    <p class="text-sm text-slate-600 leading-relaxed">{{ $campaign->description }}</p>
                </x-ui.card>
            @endif

            {{-- Recent Donations --}}
            <x-ui.card title="Recent Donations" description="Last 10 donations to this campaign">
                @if ($this->recentDonations->isNotEmpty())
                    <div class="divide-y divide-slate-100">
                        @foreach ($this->recentDonations as $donation)
                            <div class="flex items-center justify-between py-3">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-50">
                                        <x-heroicon-o-user class="size-4 text-slate-400" />
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-slate-900">{{ $donation->donor?->full_name ?? 'Anonymous' }}</p>
                                        <p class="text-xs text-slate-500">{{ $donation->created_at->format('M d, Y H:i') }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-semibold text-slate-900">RM {{ number_format((float) $donation->gross_amount, 2) }}</p>
                                    <x-ui.badge status="{{ $donation->status->value }}" size="sm">{{ ucfirst($donation->status->value) }}</x-ui.badge>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <x-ui.empty-state
                        icon="heroicon-o-banknotes"
                        title="No donations yet"
                        description="Donations to this campaign will appear here."
                    />
                @endif
            </x-ui.card>
        </div>

        {{-- Right Column --}}
        <div class="space-y-6">
            {{-- Campaign Details --}}
            <x-ui.card title="Details">
                <dl class="space-y-3">
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Campaign ID</dt>
                        <dd class="mt-0.5 text-sm text-slate-900 font-mono">{{ $campaign->public_id }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Form Parameter</dt>
                        <dd class="mt-0.5 text-sm text-slate-900 font-mono">{{ $campaign->form_parameter }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Recurring</dt>
                        <dd class="mt-0.5 text-sm text-slate-900">{{ $campaign->allow_recurring ? 'Allowed' : 'Not allowed' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Custom Amount</dt>
                        <dd class="mt-0.5 text-sm text-slate-900">{{ $campaign->allow_custom_amount ? 'Allowed' : 'Not allowed' }}</dd>
                    </div>
                    @if ($campaign->minimum_amount)
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Minimum Amount</dt>
                            <dd class="mt-0.5 text-sm text-slate-900">RM {{ number_format((float) $campaign->minimum_amount, 2) }}</dd>
                        </div>
                    @endif
                </dl>
            </x-ui.card>

            {{-- Linked Elements --}}
            <x-ui.card title="Linked Elements" description="Embed elements using this campaign">
                @if ($this->elements->isNotEmpty())
                    <div class="divide-y divide-slate-100">
                        @foreach ($this->elements as $element)
                            <div class="flex items-center justify-between py-3">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-50">
                                        <x-heroicon-o-code-bracket class="size-4 text-slate-400" />
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-slate-900">{{ $element->name }}</p>
                                        <p class="text-xs text-slate-500">{{ ucfirst($element->type->value) }}</p>
                                    </div>
                                </div>
                                <code class="rounded bg-slate-50 px-2 py-0.5 text-xs text-slate-600 font-mono">{{ $element->public_id }}</code>
                            </div>
                        @endforeach
                    </div>
                @else
                    <x-ui.empty-state
                        icon="heroicon-o-code-bracket"
                        title="No elements"
                        description="Create an embed element linked to this campaign."
                    />
                @endif
            </x-ui.card>
        </div>
    </div>

    {{-- Back Link --}}
    <div>
        <a href="{{ route('app.campaigns.index') }}" wire:navigate class="inline-flex items-center text-sm font-medium text-teal-600 hover:text-teal-700">
            <x-heroicon-o-arrow-left class="size-4 mr-1" />
            Back to campaigns
        </a>
    </div>
</div>
