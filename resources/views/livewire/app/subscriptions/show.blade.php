{{-- resources/views/livewire/app/subscriptions/show.blade.php --}}
<div
    class="space-y-6"
    x-data="{
        copied: false,
        activeSection: 'section-subscription',
        init() {
            const sections = document.querySelectorAll('[data-section]');
            if (sections.length === 0) {
                return;
            }

            const observer = new IntersectionObserver(
                (entries) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting) {
                            this.activeSection = entry.target.dataset.section;
                        }
                    });
                },
                { rootMargin: '-15% 0px -55% 0px', threshold: 0 },
            );

            sections.forEach((section) => observer.observe(section));
        },
        scrollToSection(id) {
            const element = document.getElementById(id);
            if (element) {
                element.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        },
    }"
>
    {{-- Page Header --}}
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-slate-900">{{ $this->subscription->currency_symbol }}{{ number_format((float) $this->subscription->amount, 2) }} {{ strtoupper($this->subscription->currency) }} recurring plan</h1>
            <p class="mt-1 flex items-center gap-2 text-sm text-slate-500">
                <span>ID {{ $subscription->public_id }}</span>
                <button
                    x-on:click="navigator.clipboard.writeText('{{ $subscription->public_id }}'); copied = true; setTimeout(() => copied = false, 2000)"
                    class="inline-flex items-center rounded p-0.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600"
                    title="Copy subscription ID"
                >
                    <x-heroicon-o-clipboard-document class="size-3.5" />
                </button>
                <span x-show="copied" x-transition class="text-xs text-emerald-600">Copied!</span>
                <span>· Total {{ $this->totalMyrAmount['hasApproximation'] ? '≈' : '' }} MYR {{ number_format($this->totalMyrAmount['amount'], 2) }}</span>
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-[1fr_18rem] xl:grid-cols-[1fr_20rem]">
        {{-- Left Column --}}
        <div class="space-y-6">
            {{-- Subscription --}}
            <section id="section-subscription" data-section="section-subscription">
                <x-ui.card title="Recurring plan" icon="heroicon-o-arrow-path">
                    <x-slot:actions>
                        <button type="button" wire:click="openEditModal" class="text-sm font-medium text-teal-600 hover:text-teal-700">
                            Edit
                        </button>
                    </x-slot:actions>

                    <dl class="space-y-5">
                        <div class="grid grid-cols-1 gap-1 sm:grid-cols-[180px_1fr] sm:gap-6">
                            <dt class="text-sm text-slate-500">Recurring plan ID</dt>
                            <dd class="flex items-center gap-2 text-sm font-medium text-slate-900">
                                {{ $subscription->public_id }}
                                <button
                                    x-on:click="navigator.clipboard.writeText('{{ $subscription->public_id }}'); copied = true; setTimeout(() => copied = false, 2000)"
                                    class="inline-flex items-center rounded p-0.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600"
                                    title="Copy recurring plan ID"
                                >
                                    <x-heroicon-o-clipboard-document class="size-3.5" />
                                </button>
                            </dd>
                        </div>
                        <div class="grid grid-cols-1 gap-1 sm:grid-cols-[180px_1fr] sm:gap-6">
                            <dt class="text-sm text-slate-500">Status</dt>
                            <dd class="text-sm font-medium text-slate-900">{{ $subscription->status->getLabel() }}</dd>
                        </div>
                        <div class="grid grid-cols-1 gap-1 sm:grid-cols-[180px_1fr] sm:gap-6">
                            <dt class="text-sm text-slate-500">Supporter</dt>
                            <dd class="text-sm font-medium">
                                @if ($subscription->donor)
                                    <a href="{{ route('app.supporters.show', $subscription->donor) }}" wire:navigate class="text-blue-600 hover:text-blue-700">
                                        {{ $subscription->donor->name }}
                                    </a>
                                @else
                                    <span class="text-slate-900">Unknown</span>
                                @endif
                            </dd>
                        </div>
                        <div class="grid grid-cols-1 gap-1 sm:grid-cols-[180px_1fr] sm:gap-6">
                            <dt class="text-sm text-slate-500">Campaign</dt>
                            <dd class="text-sm font-medium">
                                @if ($subscription->campaign)
                                    <a href="{{ route('app.campaigns.edit', $subscription->campaign) }}" wire:navigate class="text-blue-600 hover:text-blue-700">
                                        {{ $subscription->campaign->title }}
                                    </a>
                                @else
                                    <span class="text-slate-500">No campaign linked</span>
                                @endif
                            </dd>
                        </div>
                        <div class="grid grid-cols-1 gap-1 sm:grid-cols-[180px_1fr] sm:gap-6">
                            <dt class="text-sm text-slate-500">Installment amount</dt>
                            <dd class="flex flex-wrap items-center gap-3 text-sm font-medium text-slate-900">
                                <span>{{ $this->formattedAmount() }}</span>
                                @php
                                    $latestBaseAmount = $this->latestDonation?->base_amount;
                                @endphp
                                @if (strtolower($subscription->currency) !== 'myr' && $latestBaseAmount !== null)
                                    <span class="text-slate-400">≈ {{ $this->latestDonation->base_currency ? strtoupper($this->latestDonation->base_currency) : 'MYR' }} {{ number_format((float) $latestBaseAmount, 2) }}</span>
                                @elseif(strtolower($subscription->currency) !== 'myr')
                                    <span class="text-slate-400">≈ MYR —</span>
                                @endif
                                <button
                                    type="button"
                                    wire:click="openUpgradeModal"
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-1 text-xs font-medium text-slate-600 transition hover:bg-slate-50"
                                >
                                    Offer an increase
                                </button>
                            </dd>
                        </div>
                        <div class="grid grid-cols-1 gap-1 sm:grid-cols-[180px_1fr] sm:gap-6">
                            <dt class="text-sm text-slate-500">Fee covered</dt>
                            <dd class="text-sm font-medium text-slate-900">{{ $subscription->cover_fee ? 'Covered' : 'Not covered' }}</dd>
                        </div>
                        <div class="grid grid-cols-1 gap-1 sm:grid-cols-[180px_1fr] sm:gap-6">
                            <dt class="text-sm text-slate-500">Total donated to date</dt>
                            <dd class="text-sm font-medium text-slate-900">
                                {{ $this->totalMyrAmount['hasApproximation'] ? '≈' : '' }} MYR {{ number_format($this->totalMyrAmount['amount'], 2) }}
                            </dd>
                        </div>
                        <div class="grid grid-cols-1 gap-1 sm:grid-cols-[180px_1fr] sm:gap-6">
                            <dt class="text-sm text-slate-500">Frequency</dt>
                            <dd class="text-sm text-slate-900">{{ $this->frequencyLabel() }}</dd>
                        </div>
                        <div class="grid grid-cols-1 gap-1 sm:grid-cols-[180px_1fr] sm:gap-6">
                            <dt class="text-sm text-slate-500">Creation date</dt>
                            <dd class="text-sm text-slate-900">{{ $subscription->created_at->format('M d, Y, g:i A') }}</dd>
                        </div>
                        <div class="grid grid-cols-1 gap-1 sm:grid-cols-[180px_1fr] sm:gap-6">
                            <dt class="text-sm text-slate-500">Last installment date</dt>
                            <dd class="w-fit border-b border-dashed border-slate-300 pb-0.5 text-sm text-slate-900">
                                {{ $this->lastInstallmentDate ?? '—' }}
                            </dd>
                        </div>
                        <div class="grid grid-cols-1 gap-1 sm:grid-cols-[180px_1fr] sm:gap-6">
                            <dt class="text-sm text-slate-500">Next installment date</dt>
                            <dd class="w-fit border-b border-dashed border-slate-300 pb-0.5 text-sm text-slate-900">
                                {{ $subscription->current_period_end?->format('M d, Y, g:i A') ?? '—' }}
                            </dd>
                        </div>
                        <div class="grid grid-cols-1 gap-1 sm:grid-cols-[180px_1fr] sm:gap-6">
                            <dt class="text-sm text-slate-500">Payment method</dt>
                            <dd class="flex items-center gap-2 text-sm font-medium text-slate-900">
                                <x-heroicon-o-credit-card class="size-4 text-slate-400" />
                                {{ $this->latestDonation?->payment_method_type ? ucfirst($this->latestDonation->payment_method_type) : 'Credit Card' }}
                            </dd>
                        </div>
                        @if ($this->latestDonation?->payment_method_brand || $this->latestDonation?->payment_method_last4)
                            <div class="grid grid-cols-1 gap-1 sm:grid-cols-[180px_1fr] sm:gap-6">
                                <dt class="text-sm text-slate-500">Credit card</dt>
                                <dd class="flex items-center gap-2 text-sm font-medium text-slate-900">
                                    @php
                                        $cardIcon = match (strtolower($this->latestDonation->payment_method_brand ?? '')) {
                                            'visa' => 'icons.visa',
                                            'mastercard' => 'icons.mastercard',
                                            'amex' => 'icons.amex',
                                            'discover' => 'icons.discover',
                                            'diners' => 'icons.diners',
                                            'jcb' => 'icons.jcb',
                                            'maestro' => 'icons.maestro',
                                            'unionpay' => 'icons.unionpay',
                                            default => 'heroicon-o-credit-card',
                                        };
                                    @endphp
                                    <x-dynamic-component :component="$cardIcon" class="size-6" />
                                    {{ $this->latestDonation->payment_method_brand ? ucfirst($this->latestDonation->payment_method_brand) : 'Card' }}
                                    @if ($this->latestDonation->payment_method_last4)
                                        <span class="text-slate-500">•••• {{ $this->latestDonation->payment_method_last4 }}</span>
                                    @endif
                                </dd>
                            </div>
                        @endif
                    </dl>
                </x-ui.card>
            </section>

            {{-- Personal Information --}}
            <section id="section-personal" data-section="section-personal">
                <x-ui.card title="Personal information" icon="heroicon-o-user">
                    <x-slot:actions>
                        @if ($subscription->donor)
                            <button type="button" wire:click="openEditPersonalModal" class="text-sm font-medium text-teal-600 hover:text-teal-700">
                                Edit
                            </button>
                        @endif
                    </x-slot:actions>
                    @if ($subscription->donor)
                        <dl class="space-y-5">
                            <div class="grid grid-cols-1 gap-1 sm:grid-cols-[180px_1fr] sm:gap-6">
                                <dt class="text-sm text-slate-500">Name</dt>
                                <dd class="text-sm font-medium text-slate-900">{{ $subscription->donor->name }}</dd>
                            </div>
                            <div class="grid grid-cols-1 gap-1 sm:grid-cols-[180px_1fr] sm:gap-6">
                                <dt class="text-sm text-slate-500">Email</dt>
                                <dd class="text-sm font-medium text-slate-900">{{ $subscription->donor->email }}</dd>
                            </div>
                        </dl>
                    @else
                        <p class="text-sm text-slate-500">No supporter linked</p>
                    @endif
                </x-ui.card>
            </section>

            {{-- Source --}}
            <section id="section-source" data-section="section-source">
                <x-ui.card title="Source" icon="heroicon-o-arrow-top-right-on-square">
                    @php
                        $firstDonation = $subscription->donations()->first();
                        $utm = is_string($firstDonation?->utm_params) ? json_decode($firstDonation->utm_params, true) : ($firstDonation?->utm_params ?? []);
                        $elementId = $utm['element_id'] ?? null;
                        $element = $elementId ? \App\Models\Element::find($elementId) : null;
                    @endphp
                    <dl class="space-y-5">
                        <div class="grid grid-cols-1 gap-1 sm:grid-cols-[180px_1fr] sm:gap-6">
                            <dt class="text-sm text-slate-500">Source</dt>
                            <dd class="text-sm font-medium text-slate-900">{{ $subscription->source ? ucfirst(str_replace('_', ' ', $subscription->source)) : 'Checkout Modal' }}</dd>
                        </div>
                        <div class="grid grid-cols-1 gap-1 sm:grid-cols-[180px_1fr] sm:gap-6">
                            <dt class="text-sm text-slate-500">URL</dt>
                            <dd class="text-sm font-medium">
                                @if ($firstDonation?->page_url)
                                    <a href="{{ $firstDonation->page_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 text-blue-600 hover:text-blue-700">
                                        {{ $firstDonation->page_url }}
                                        <x-heroicon-o-arrow-top-right-on-square class="size-4" />
                                    </a>
                                @else
                                    <span class="text-slate-500">—</span>
                                @endif
                            </dd>
                        </div>
                        <div class="grid grid-cols-1 gap-1 sm:grid-cols-[180px_1fr] sm:gap-6">
                            <dt class="text-sm text-slate-500">Element</dt>
                            <dd class="text-sm font-medium">
                                @if ($firstDonation?->element_label && $element)
                                    <a href="{{ route('app.elements.edit', $element->public_id) }}" wire:navigate class="inline-flex items-center gap-1 text-blue-600 hover:text-blue-700">
                                        <x-heroicon-o-squares-2x2 class="size-4" />
                                        {{ $firstDonation->element_label }}
                                    </a>
                                @elseif ($firstDonation?->element_label)
                                    <span class="inline-flex items-center gap-1 text-slate-900">
                                        <x-heroicon-o-squares-2x2 class="size-4" />
                                        {{ $firstDonation->element_label }}
                                    </span>
                                @else
                                    <span class="text-slate-500">—</span>
                                @endif
                            </dd>
                        </div>
                    </dl>
                </x-ui.card>
            </section>

            {{-- Recent Payments --}}
            <section id="section-payments" data-section="section-payments">
                <x-ui.card title="Installments" icon="heroicon-o-receipt-percent">
                    @if ($this->recentPayments->isNotEmpty())
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-left text-sm">
                                <thead>
                                    <tr class="border-b border-slate-100">
                                        <th class="py-2 pr-4 font-medium text-slate-900">Donation ID</th>
                                        <th class="py-2 pr-4 font-medium text-slate-900">Amount</th>
                                        <th class="py-2 pr-4 font-medium text-slate-900">Campaign</th>
                                        <th class="py-2 font-medium text-slate-900">Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($this->recentPayments as $payment)
                                        <tr class="border-b border-slate-50 last:border-0">
                                            <td class="py-3 pr-4">
                                                <a href="{{ route('app.donations.show', $payment) }}" wire:navigate class="font-medium text-blue-600 hover:text-blue-700">
                                                    {{ $payment->public_id }}
                                                </a>
                                            </td>
                                            <td class="py-3 pr-4 font-medium text-slate-900">{{ $payment->formatted_amount }}</td>
                                            <td class="py-3 pr-4 text-slate-600">
                                                @if ($payment->campaign)
                                                    {{ $payment->campaign->title }}
                                                @else
                                                    <span class="text-slate-400">—</span>
                                                @endif
                                            </td>
                                            <td class="py-3 pr-4 text-slate-600">{{ $payment->created_at->format('M d, Y') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <x-ui.empty-state
                            icon="heroicon-o-banknotes"
                            title="No payments yet"
                            description="Payments for this subscription will appear here."
                        />
                    @endif
                </x-ui.card>
            </section>

            {{-- Receipts --}}
            <section id="section-receipts" data-section="section-receipts">
                <x-ui.card title="Receipts" icon="heroicon-o-document-text">
                    @if ($this->receiptDonations->isNotEmpty())
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-left text-sm">
                                <thead>
                                    <tr class="border-b border-slate-100">
                                        <th class="py-2 pr-4 font-medium text-slate-900">Receipt number</th>
                                        <th class="py-2 pr-4 font-medium text-slate-900">Amount</th>
                                        <th class="py-2 pr-4 font-medium text-slate-900">Donation date</th>
                                        <th class="py-2"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($this->receiptDonations as $donation)
                                        <tr class="border-b border-slate-50 last:border-0">
                                            <td class="py-3 pr-4">
                                                <div class="flex items-center gap-2">
                                                    @if ($donation->status->value === 'succeeded')
                                                        <x-heroicon-o-check-circle class="size-5 text-emerald-500" />
                                                        <span class="font-medium text-slate-900">{{ $donation->public_id }}</span>
                                                    @else
                                                        <span class="font-medium text-slate-900">{{ $donation->public_id }}</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="py-3 pr-4 font-medium text-slate-900">
                                                {{ $donation->currency_symbol }} {{ number_format((float) $donation->gross_amount, 2) }}
                                            </td>
                                            <td class="py-3 pr-4 text-slate-600">{{ $donation->created_at->format('M d, Y') }}</td>
                                            <td class="py-3 text-right">
                                                @if ($donation->status->value === 'succeeded')
                                                    <a href="{{ route('donations.receipt.download', ['donation' => $donation->public_id]) }}" target="_blank" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-600 hover:text-slate-900">
                                                        <x-heroicon-o-arrow-down-tray class="size-4" />
                                                        Download
                                                    </a>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <x-ui.empty-state
                            icon="heroicon-o-document-text"
                            title="No receipts yet"
                            description="Receipts for donations in this plan will appear here."
                        />
                    @endif
                </x-ui.card>
            </section>
        </div>

        {{-- Right Column / Floating Menu --}}
        <div class="space-y-4">
            <div class="lg:sticky lg:top-6 lg:self-start space-y-4">
                {{-- Floating Action Menu --}}
                <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <button
                        wire:click="$set('showUpgradeModal', true)"
                        class="flex w-full items-center gap-3 border-b border-slate-100 px-4 py-3 text-left text-sm text-slate-700 transition hover:bg-slate-50"
                    >
                        <x-heroicon-o-pencil class="size-5 text-slate-400" />
                        Edit payment details
                    </button>
                    <button
                        wire:click="pauseSubscription()"
                        class="flex w-full items-center gap-3 border-b border-slate-100 px-4 py-3 text-left text-sm text-slate-700 transition hover:bg-slate-50"
                    >
                        <x-heroicon-o-arrow-right-circle class="size-5 text-slate-400" />
                        Skip installments
                    </button>
                    <button
                        wire:click="openUpgradeModal()"
                        class="flex w-full items-center gap-3 border-b border-slate-100 px-4 py-3 text-left text-sm text-slate-700 transition hover:bg-slate-50"
                    >
                        <x-heroicon-o-arrow-up-circle class="size-5 text-slate-400" />
                        Offer plan upgrade
                    </button>
                    <button
                        wire:click="cancelSubscription()"
                        onclick="return confirm('Are you sure you want to cancel this subscription?')"
                        class="flex w-full items-center gap-3 px-4 py-3 text-left text-sm text-red-600 transition hover:bg-red-50"
                    >
                        <x-heroicon-o-trash class="size-5 text-red-400" />
                        Cancel recurring
                    </button>
                </div>

                {{-- Navigation --}}
                <div class="overflow-hidden rounded-xl border border-slate-200 bg-white p-1.5">
                    <nav class="space-y-0.5" aria-label="Subscription sections">
                        <button
                            type="button"
                            @click="scrollToSection('section-subscription')"
                            :class="activeSection === 'section-subscription' ? 'bg-slate-100 text-slate-900' : 'text-slate-600 hover:bg-slate-50'"
                            class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left text-sm font-semibold transition"
                        >
                            <x-heroicon-o-arrow-path class="size-5" />
                            Recurring plans
                        <button
                            type="button"
                            @click="scrollToSection('section-personal')"
                            :class="activeSection === 'section-personal' ? 'bg-slate-100 text-slate-900' : 'text-slate-600 hover:bg-slate-50'"
                            class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left text-sm font-semibold transition"
                        >
                            <x-heroicon-o-user class="size-5" />
                            Personal information
                        </button>
                        <button
                            type="button"
                            @click="scrollToSection('section-source')"
                            :class="activeSection === 'section-source' ? 'bg-slate-100 text-slate-900' : 'text-slate-600 hover:bg-slate-50'"
                            class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left text-sm font-semibold transition"
                        >
                            <x-heroicon-o-arrow-top-right-on-square class="size-5" />
                            Source
                        </button>
                        <button
                            type="button"
                            @click="scrollToSection('section-payments')"
                            :class="activeSection === 'section-payments' ? 'bg-slate-100 text-slate-900' : 'text-slate-600 hover:bg-slate-50'"
                            class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left text-sm font-semibold transition"
                        >
                            <x-heroicon-o-receipt-percent class="size-5" />
                            Installments
                        </button>
                        <button
                            type="button"
                            @click="scrollToSection('section-receipts')"
                            :class="activeSection === 'section-receipts' ? 'bg-slate-100 text-slate-900' : 'text-slate-600 hover:bg-slate-50'"
                            class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left text-sm font-semibold transition"
                        >
                            <x-heroicon-o-document-text class="size-5" />
                            Receipts
                        </button>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    {{-- Back Link --}}
    <div>
        <a href="{{ route('app.subscriptions.index') }}" wire:navigate class="inline-flex items-center text-sm font-medium text-teal-600 hover:text-teal-700">
            <x-heroicon-o-arrow-left class="size-4 mr-1" />
            Back to subscriptions
        </a>
    </div>

    {{-- Edit Subscription Modal --}}
    <flux:modal wire:model="showEditModal" name="edit-subscription-modal">
        <div class="space-y-4">
            <h3 class="text-lg font-semibold text-slate-900">Edit recurring</h3>

            <p class="text-sm text-slate-600">
                Only the campaign can be updated for this subscription.
            </p>

            <flux:select wire:model="editCampaignId" label="Campaign">
                @php
                    $campaigns = $subscription->campaign?->organization?->campaigns ?? collect();
                @endphp
                @foreach ($campaigns as $campaign)
                    <flux:select.option value="{{ $campaign->id }}">{{ $campaign->title }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="flex justify-end gap-3 pt-2">
                <flux:modal.close>
                    <x-ui.button wireClick="closeEditModal" variant="secondary">Cancel</x-ui.button>
                </flux:modal.close>
                <x-ui.button wireClick="saveSubscription" variant="primary">Save changes</x-ui.button>
            </div>
        </div>
    </flux:modal>

    {{-- Upgrade Amount Modal --}}
    <flux:modal wire:model="showUpgradeModal" name="upgrade-amount-modal">
        <div class="space-y-4">
            <h3 class="text-lg font-semibold text-slate-900">Offer plan upgrade</h3>

            <p class="text-sm text-slate-600">
                Update the recurring amount for this subscription.
            </p>

            <flux:input wire:model="newAmount" label="New amount" type="number" step="0.01" min="1" max="99999.99" />

            <div class="flex justify-end gap-3 pt-2">
                <flux:modal.close>
                    <x-ui.button wireClick="closeUpgradeModal" variant="secondary">Cancel</x-ui.button>
                </flux:modal.close>
                <x-ui.button wireClick="upgradeAmount" variant="primary">Save changes</x-ui.button>
            </div>
        </div>
    </flux:modal>

    {{-- Edit Personal Information Modal --}}
    <flux:modal wire:model="showEditPersonalModal" name="edit-personal-information-modal">
        <div class="space-y-4">
            <h3 class="text-lg font-semibold text-slate-900">Edit personal information</h3>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <flux:input wire:model="editFirstName" label="First name" />
                <flux:input wire:model="editLastName" label="Last name" />
            </div>

            <flux:input wire:model="editEmail" label="Email" type="email" />

            <flux:input wire:model="editPhone" label="Phone number" />

            <flux:input wire:model="editAddressLine1" label="Mailing address" placeholder="Street address" />
            <flux:input wire:model="editAddressLine2" placeholder="Apartment / suite / floor" />

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <flux:input wire:model="editAddressCity" placeholder="City" />
                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="editAddressState" placeholder="State" />
                    <flux:input wire:model="editAddressPostalCode" placeholder="Zip code" />
                </div>
            </div>

            <flux:select wire:model="editCountry" placeholder="Country">
                <flux:select.option value="">Country</flux:select.option>
                <flux:select.option value="MY">Malaysia</flux:select.option>
                <flux:select.option value="SG">Singapore</flux:select.option>
                <flux:select.option value="ID">Indonesia</flux:select.option>
                <flux:select.option value="TH">Thailand</flux:select.option>
                <flux:select.option value="PH">Philippines</flux:select.option>
                <flux:select.option value="VN">Vietnam</flux:select.option>
                <flux:select.option value="US">United States</flux:select.option>
                <flux:select.option value="CA">Canada</flux:select.option>
                <flux:select.option value="GB">United Kingdom</flux:select.option>
                <flux:select.option value="AU">Australia</flux:select.option>
                <flux:select.option value="NZ">New Zealand</flux:select.option>
                <flux:select.option value="IN">India</flux:select.option>
                <flux:select.option value="JP">Japan</flux:select.option>
                <flux:select.option value="KR">South Korea</flux:select.option>
                <flux:select.option value="CN">China</flux:select.option>
                <flux:select.option value="HK">Hong Kong</flux:select.option>
                <flux:select.option value="TW">Taiwan</flux:select.option>
                <flux:select.option value="AE">United Arab Emirates</flux:select.option>
                <flux:select.option value="SA">Saudi Arabia</flux:select.option>
                <flux:select.option value="QA">Qatar</flux:select.option>
                <flux:select.option value="TR">Turkey</flux:select.option>
            </flux:select>

            <div class="flex justify-end gap-3 pt-2">
                <flux:modal.close>
                    <x-ui.button wireClick="closeEditPersonalModal" variant="secondary">Cancel</x-ui.button>
                </flux:modal.close>
                <x-ui.button wireClick="savePersonalInformation" variant="primary">Save changes</x-ui.button>
            </div>
        </div>
    </flux:modal>
</div>
