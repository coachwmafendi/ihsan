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
            <h1 class="text-3xl font-bold tracking-tight text-slate-900">{{ $this->subscription->displayAmount() }} recurring plan</h1>
            <p class="mt-1 flex items-center gap-2 text-sm text-slate-500">
                <span>ID {{ $subscription->public_id }}</span>
                <x-ui.tooltip text="Copy subscription ID">
                    <button
                        x-on:click="navigator.clipboard.writeText('{{ $subscription->public_id }}'); copied = true; setTimeout(() => copied = false, 2000)"
                        class="inline-flex items-center rounded p-0.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600"
                    >
                        <x-heroicon-o-clipboard-document class="size-3.5" />
                    </button>
                </x-ui.tooltip>
                <span x-show="copied" x-transition class="text-xs text-emerald-600">Copied!</span>
                <x-ui.tooltip :text="$this->originalAmounts->isNotEmpty() ? $this->originalAmounts->map(fn ($amount, $currency) => $currency.' '.number_format($amount, 2))->join(', ') : ''">
                    <span>· Total {{ $this->totalMyrAmount['hasApproximation'] ? '≈' : '' }} MYR {{ number_format($this->totalMyrAmount['amount'], 2) }}</span>
                </x-ui.tooltip>
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-[1fr_18rem] xl:grid-cols-[1fr_20rem]">
        {{-- Left Column --}}
        <div class="space-y-6">
            {{-- Status Banner --}}
            @php
                $intervalLabel = strtolower($subscription->interval->value);
                $dateClass = 'border-b border-dashed border-slate-400';
            @endphp

            @switch($subscription->status->value)
                @case('active')
                    @if ($subscription->cancel_at_period_end)
                        <x-ui.alert :compact="true" variant="warning" icon="heroicon-o-exclamation-triangle">
                            Scheduled to cancel. This {{ $intervalLabel }} recurring donation's final installment will be charged on <span class="{{ $dateClass }}">{{ myrTime($this->nextInstallmentDate) }}</span>.
                        </x-ui.alert>
                    @else
                        <x-ui.alert :compact="true" variant="info" icon="heroicon-o-information-circle">
                            This {{ $intervalLabel }} recurring donation is active. Installment #{{ $this->nextInstallmentNumber }} will be charged on <span class="{{ $dateClass }}">{{ myrTime($this->nextInstallmentDate) }}</span>.
                        </x-ui.alert>
                    @endif
                    @break

                @case('paused')
                    <x-ui.alert :compact="true" variant="warning" icon="heroicon-o-pause-circle">
                        @if ($subscription->paused_until)
                            This {{ $intervalLabel }} recurring donation is paused. Installments will resume on <span class="{{ $dateClass }}">{{ myrTime($subscription->paused_until) }}</span>.
                        @else
                            This {{ $intervalLabel }} recurring donation is paused. Installments are currently on hold.
                        @endif
                    </x-ui.alert>
                    @break

                @case('cancelled')
                    <x-ui.alert :compact="true" variant="danger" icon="heroicon-o-x-circle">
                        @if ($subscription->cancelled_at)
                            This recurring donation was cancelled on <span class="{{ $dateClass }}">{{ myrTime($subscription->cancelled_at) }}</span>. No further charges will be made.
                        @else
                            This recurring donation has been cancelled. No further charges will be made.
                        @endif
                    </x-ui.alert>
                    @break

                @case('past_due')
                    <x-ui.alert :compact="true" variant="danger" icon="heroicon-o-exclamation-circle">
                        The most recent installment failed{{ filled($this->lastInstallmentDate) ? ' on '.$this->lastInstallmentDate : '' }}. We will retry the payment{{ $this->nextInstallmentDate ? ' on '.myrTime($this->nextInstallmentDate) : ' shortly' }}.
                    </x-ui.alert>
                    @break

                @case('failed')
                    <x-ui.alert :compact="true" variant="danger" icon="heroicon-o-x-circle">
                        This recurring plan has failed after repeated payment attempts. No further charges will be attempted.
                    </x-ui.alert>
                    @break

                @case('incomplete')
                    <x-ui.alert :compact="true" variant="warning" icon="heroicon-o-exclamation-triangle">
                        This recurring donation is incomplete. Please complete the payment setup.
                    </x-ui.alert>
                    @break

                @case('incomplete_expired')
                    <x-ui.alert :compact="true" variant="warning" icon="heroicon-o-clock">
                        This recurring donation setup expired because the payment was not completed in time.
                    </x-ui.alert>
                    @break

                @case('completed')
                    <x-ui.alert :compact="true" variant="success" icon="heroicon-o-check-badge">
                        This recurring donation has completed all planned installments.
                    </x-ui.alert>
                    @break
            @endswitch

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
                                <x-ui.tooltip text="Copy recurring plan ID">
                                    <button
                                        x-on:click="navigator.clipboard.writeText('{{ $subscription->public_id }}'); copied = true; setTimeout(() => copied = false, 2000)"
                                        class="inline-flex items-center rounded p-0.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600"
                                    >
                                        <x-heroicon-o-clipboard-document class="size-3.5" />
                                    </button>
                                </x-ui.tooltip>
                            </dd>
                        </div>
                        <div class="grid grid-cols-1 gap-1 sm:grid-cols-[180px_1fr] sm:gap-6">
                            <dt class="text-sm text-slate-500">Status</dt>
                            <dd class="flex flex-wrap items-center gap-2 text-sm font-medium text-slate-900">
                                <span>{{ $subscription->status_label }}</span>
                                @if ($this->isTerminalStatus)
                                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700">Ended</span>
                                @endif
                            </dd>
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
                                @if ($this->canManageRecurringPlan)
                                    <button
                                        type="button"
                                        wire:click="openUpgradeModal"
                                        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-1 text-xs font-medium text-slate-600 transition hover:bg-slate-50"
                                    >
                                        Offer an increase
                                    </button>
                                @endif
                            </dd>
                        </div>
                        <div class="grid grid-cols-1 gap-1 sm:grid-cols-[180px_1fr] sm:gap-6">
                            <dt class="text-sm text-slate-500">Fee covered</dt>
                            <dd class="text-sm font-medium text-slate-900">{{ $subscription->cover_fee ? 'Covered' : 'Not covered' }}</dd>
                        </div>
                        <div class="grid grid-cols-1 gap-1 sm:grid-cols-[180px_1fr] sm:gap-6">
                            <dt class="text-sm text-slate-500">Total donated to date</dt>
                            <dd class="text-sm font-medium text-slate-900">
                                <x-ui.tooltip :text="$this->originalAmounts->isNotEmpty() ? $this->originalAmounts->map(fn ($amount, $currency) => $currency.' '.number_format($amount, 2))->join(', ') : ''">
                                    <span>{{ $this->totalMyrAmount['hasApproximation'] ? '≈' : '' }} MYR {{ number_format($this->totalMyrAmount['amount'], 2) }}</span>
                                </x-ui.tooltip>
                            </dd>
                        </div>
                        <div class="grid grid-cols-1 gap-1 sm:grid-cols-[180px_1fr] sm:gap-6">
                            <dt class="text-sm text-slate-500">Frequency</dt>
                            <dd class="text-sm text-slate-900">{{ $this->frequencyLabel() }}</dd>
                        </div>
                        <div class="grid grid-cols-1 gap-1 sm:grid-cols-[180px_1fr] sm:gap-6">
                            <dt class="text-sm text-slate-500">Creation date</dt>
                            <dd class="text-sm text-slate-900">{{ myrTime($subscription->created_at) }}</dd>
                        </div>
                        <div class="grid grid-cols-1 gap-1 sm:grid-cols-[180px_1fr] sm:gap-6">
                            <dt class="text-sm text-slate-500">Last installment date</dt>
                            <dd class="w-fit border-b border-dashed border-slate-300 pb-0.5 text-sm text-slate-900">
                                {{ $this->lastInstallmentDate ?? '—' }}
                            </dd>
                        </div>
                        <div class="grid grid-cols-1 gap-1 sm:grid-cols-[180px_1fr] sm:gap-6">
                            <dt class="text-sm text-slate-500">
                                {{ $this->isScheduledToCancel ? 'Final installment date' : 'Next installment date' }}
                            </dt>
                            <dd class="w-fit border-b border-dashed border-slate-300 pb-0.5 text-sm text-slate-900">
                                {{ myrTime($this->nextInstallmentDate) }}
                            </dd>
                        </div>
                        <div class="grid grid-cols-1 gap-1 sm:grid-cols-[180px_1fr] sm:gap-6">
                            <dt class="text-sm text-slate-500">Payment method</dt>
                            <dd class="flex items-center gap-2 text-sm font-medium text-slate-900">
                                @php
                                    $pmType = strtolower($this->latestDonation?->payment_method_type ?? '');
                                    $pmBrand = strtolower($this->latestDonation?->payment_method_brand ?? '');
                                @endphp
                                @if ($pmBrand === 'apple_pay' || $pmType === 'apple_pay')
                                    <x-icons.apple-pay class="h-4 w-auto text-slate-700" />
                                @elseif ($pmBrand === 'google_pay' || $pmType === 'google_pay')
                                    <x-icons.google-pay class="h-4 w-auto text-slate-700" />
                                @else
                                    <x-heroicon-o-credit-card class="size-4 text-slate-400" />
                                    <span>{{ $this->latestDonation?->payment_method_type ? ucfirst($this->latestDonation->payment_method_type) : 'Credit Card' }}</span>
                                @endif
                            </dd>
                        </div>
                        @if ($this->latestDonation?->payment_method_brand || $this->latestDonation?->payment_method_exp_month || $this->latestDonation?->payment_method_last4)
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

                                        $expiryMonth = $this->latestDonation->payment_method_exp_month
                                            ? str_pad((string) $this->latestDonation->payment_method_exp_month, 2, '0', STR_PAD_LEFT)
                                            : null;
                                        $expiryYear = $this->latestDonation->payment_method_exp_year
                                            ? substr((string) $this->latestDonation->payment_method_exp_year, -2)
                                            : null;
                                    @endphp
                                    <x-dynamic-component :component="$cardIcon" class="size-6" />
                                    {{ $this->latestDonation->payment_method_brand ? ucfirst($this->latestDonation->payment_method_brand) : 'Card' }}
                                    @if ($expiryMonth && $expiryYear)
                                        <span class="text-slate-500">{{ $expiryMonth }}/{{ $expiryYear }}</span>
                                    @elseif ($this->latestDonation->payment_method_last4)
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
                        $element = $firstDonation?->element;
                    @endphp
                    <dl class="space-y-5">
                        <div class="grid grid-cols-1 gap-1 sm:grid-cols-[180px_1fr] sm:gap-6">
                            <dt class="text-sm text-slate-500">Source</dt>
                            <dd class="text-sm font-medium text-slate-900">{{ $subscription->source_label }}</dd>
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
                                @if ($element)
                                    <a href="{{ route('app.elements.edit', $element) }}" wire:navigate class="inline-flex items-center gap-1 text-blue-600 hover:text-blue-700">
                                        <x-heroicon-o-squares-2x2 class="size-4" />
                                        {{ $firstDonation?->element_label }}
                                    </a>
                                @elseif ($firstDonation?->element_label)
                                    <span class="inline-flex items-center gap-1 text-slate-700">
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
                <x-ui.card title="Installments" icon="heroicon-o-calendar-days">
                    @if ($this->recentPayments->isNotEmpty())
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-left text-sm">
                                <thead>
                                    <tr class="border-b border-slate-100">
                                        <th class="py-2 pr-4 font-medium text-slate-900">Donation ID</th>
                                        <th class="py-2 pr-4 font-medium text-slate-900">Amount</th>
                                        <th class="py-2 pr-4 font-medium text-slate-900">Method</th>
                                        <th class="py-2 pr-4 font-medium text-slate-900">Installment</th>
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
                                            <td class="py-3 pr-4 font-medium text-slate-900">
                                                <x-ui.tooltip :text="$payment->formatted_amount">
                                                    <span>{{ $payment->formatted_report_amount }}</span>
                                                </x-ui.tooltip>
                                            </td>
                                            <td class="py-3 pr-4">
                                                <x-ui.tooltip :text="$payment->payment_method_display">
                                                    <x-dynamic-component :component="$payment->card_icon_component" class="size-6 text-slate-700" />
                                                </x-ui.tooltip>
                                            </td>
                                            <td class="py-3 pr-4">
                                                <x-ui.tooltip :text="$payment->installment_number_label ?? '—'">
                                                    <span class="inline-flex items-center gap-1.5">
                                                        <x-heroicon-o-arrow-path class="size-4 text-slate-400" />
                                                        <span class="inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-emerald-50 px-1.5 text-xs font-medium text-emerald-700">
                                                            {{ $payment->installment_number ?? '—' }}
                                                        </span>
                                                    </span>
                                                </x-ui.tooltip>
                                            </td>
                                            <td class="py-3 pr-4 text-slate-600">
                                                @if ($payment->campaign)
                                                    {{ $payment->campaign->title }}
                                                @else
                                                    <span class="text-slate-400">—</span>
                                                @endif
                                            </td>
                                            <td class="py-3 pr-4 text-slate-600">{{ myrTime($payment->created_at, withLabel: false, format: 'M d, Y') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <x-ui.empty-state
                            icon="heroicon-o-calendar-days"
                            title="No payments yet"
                            description="Payments for this subscription will appear here."
                        />
                    @endif
                </x-ui.card>
            </section>

            {{-- Receipts --}}
            <section id="section-receipts" data-section="section-receipts">
                <x-ui.card title="Receipts" icon="heroicon-o-receipt-percent">
                    @if ($this->receiptDonations->isNotEmpty())
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-left text-sm">
                                <thead>
                                    <tr class="border-b border-slate-100">
                                        <th class="py-2 pr-4 font-medium text-slate-900">Receipt Number</th>
                                        <th class="py-2 pr-4 font-medium text-slate-900">Amount</th>
                                        <th class="py-2 pr-4 font-medium text-slate-900">Donation Date</th>
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
                                                        <span class="font-medium text-slate-900">{{ $donation->invoice_number ?? $donation->public_id }}</span>
                                                    @else
                                                        <span class="font-medium text-slate-900">{{ $donation->invoice_number ?? $donation->public_id }}</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="py-3 pr-4 font-medium text-slate-900">
                                                <x-ui.tooltip :text="$donation->formatted_amount">
                                                    <span>{{ $donation->formatted_report_amount }}</span>
                                                </x-ui.tooltip>
                                            </td>
                                            <td class="py-3 pr-4 text-slate-600">{{ myrTime($donation->created_at, withLabel: false, format: 'M d, Y') }}</td>
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
                            icon="heroicon-o-receipt-percent"
                            title="No receipts yet"
                            description="Receipts for donations in this plan will appear here."
                        />
                    @endif
                </x-ui.card>
            </section>

            {{-- Emails --}}
            <section id="section-emails" data-section="section-emails">
                <x-ui.card title="Emails" icon="heroicon-o-envelope">
                    @if ($this->emailLogs->isNotEmpty())
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead>
                                    <tr class="bg-slate-50">
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-slate-500">Sent</th>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-slate-500">Subject</th>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-slate-500">Opened</th>
                                        <th scope="col" class="px-4 py-3 text-right text-xs font-semibold tracking-wider text-slate-500"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @foreach ($this->emailLogs as $log)
                                        <tr
                                            class="cursor-pointer transition-colors hover:bg-slate-50"
                                            wire:click="previewEmail({{ $log->id }})"
                                            wire:key="email-log-{{ $log->id }}"
                                        >
                                            <td class="px-4 py-3 text-sm text-slate-500">
                                                {{ $log->sent_at ? myrTime($log->sent_at) : '—' }}
                                            </td>
                                            <td class="px-4 py-3 text-sm font-medium text-slate-900">
                                                <span class="inline-flex items-center gap-2">
                                                    {{ $log->subject }}
                                                    @if (filled($log->resent_from_id))
                                                        <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">Resent</span>
                                                    @endif
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-slate-500">
                                                {{ $log->opened_at ? myrTime($log->opened_at) : '—' }}
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                <button
                                                    type="button"
                                                    wire:click.stop="confirmResend({{ $log->id }})"
                                                    class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-600 transition hover:text-teal-600"
                                                >
                                                    <x-heroicon-o-arrow-path class="size-4" />
                                                    Resend
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <x-ui.empty-state
                            icon="heroicon-o-envelope"
                            title="No emails yet"
                            description="Emails sent for this recurring plan will appear here."
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
                    @if ($this->canManageRecurringPlan)
                        <button
                            wire:click="openEditPaymentDetailsModal"
                            class="flex w-full items-center gap-3 border-b border-slate-100 px-4 py-3 text-left text-sm text-slate-700 transition hover:bg-slate-50"
                        >
                            <x-heroicon-o-pencil class="size-5 text-slate-400" />
                            Edit payment details
                        </button>
                        <button
                            wire:click="openSkipModal"
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
                            wire:click="openCancelModal"
                            class="flex w-full items-center gap-3 px-4 py-3 text-left text-sm text-red-600 transition hover:bg-red-50"
                        >
                            <x-heroicon-o-trash class="size-5 text-red-400" />
                            Cancel recurring
                        </button>
                    @elseif ($this->isScheduledToCancel)
                        <div class="bg-amber-50 px-4 py-3 text-sm text-amber-800">
                            <p class="font-medium">Cancellation scheduled</p>
                            <p class="mt-1 text-amber-700">Final installment on {{ myrTime($this->nextInstallmentDate) }}.</p>
                        </div>
                    @else
                        <div class="px-4 py-3 text-sm text-slate-600">
                            This recurring plan has ended. No further charges will be made.
                        </div>
                    @endif
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
                        </button>
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
                            <x-heroicon-o-calendar-days class="size-5" />
                            Installments
                        </button>
                        <button
                            type="button"
                            @click="scrollToSection('section-receipts')"
                            :class="activeSection === 'section-receipts' ? 'bg-slate-100 text-slate-900' : 'text-slate-600 hover:bg-slate-50'"
                            class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left text-sm font-semibold transition"
                        >
                            <x-heroicon-o-receipt-percent class="size-5" />
                            Receipts
                        </button>
                        <button
                            type="button"
                            @click="scrollToSection('section-emails')"
                            :class="activeSection === 'section-emails' ? 'bg-slate-100 text-slate-900' : 'text-slate-600 hover:bg-slate-50'"
                            class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left text-sm font-semibold transition"
                        >
                            <x-heroicon-o-envelope class="size-5" />
                            Emails
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
            Back to Recurring Plans
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

    {{-- Edit Payment Details Modal --}}
    <flux:modal wire:model="showEditPaymentDetailsModal" name="edit-payment-details-modal">
        <div class="space-y-6" x-data="editPaymentDetailsModal()">
            <div>
                <h3 class="text-lg font-semibold text-slate-900">Edit payment details</h3>
            </div>

            <div class="space-y-4">
                {{-- Installment amount --}}
                <div class="grid grid-cols-1 items-center gap-4 sm:grid-cols-[160px_1fr]">
                    <label class="text-sm font-medium text-slate-900">Installment amount</label>
                    <div class="flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 transition focus-within:border-teal-500 focus-within:ring-1 focus-within:ring-teal-500">
                        <span class="text-base font-medium text-slate-900">{{ strtoupper($subscription->currency) }}</span>
                        <input type="number" wire:model.live="editAmount" step="0.01" min="1" max="99999.99" class="min-w-0 flex-1 border-0 bg-transparent px-1 text-base text-slate-900 outline-none placeholder:text-slate-400">
                    </div>
                </div>

                {{-- Frequency --}}
                <div class="grid grid-cols-1 items-center gap-4 sm:grid-cols-[160px_1fr]">
                    <label class="text-sm font-medium text-slate-900">Frequency</label>
                    <flux:select wire:model.live="editInterval">
                        <flux:select.option value="monthly">Monthly</flux:select.option>
                        <flux:select.option value="weekly">Weekly</flux:select.option>
                        <flux:select.option value="yearly">Yearly</flux:select.option>
                    </flux:select>
                </div>

                {{-- Starting on --}}
                <div class="grid grid-cols-1 items-center gap-4 sm:grid-cols-[160px_1fr]">
                    <label class="text-sm font-medium text-slate-900">Starting on</label>
                    <flux:select wire:model.live="editBillingDay">
                        @php
                            $ordinal = fn (int $day): string => $day.((($day % 100) >= 11 && ($day % 100) <= 13) ? 'th' : match ($day % 10) { 1 => 'st', 2 => 'nd', 3 => 'rd', default => 'th' });
                        @endphp
                        @for ($day = 1; $day <= 28; $day++)
                            <flux:select.option value="{{ $day }}">{{ $ordinal($day) }}</flux:select.option>
                        @endfor
                    </flux:select>
                </div>

                {{-- Process a payment now --}}
                <div class="grid grid-cols-1 items-center gap-4 sm:grid-cols-[160px_1fr]">
                    <div></div>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" wire:model="editProcessPaymentNow" class="size-4 rounded border-slate-300 text-teal-600 focus:ring-teal-600">
                        <span class="text-sm text-slate-700">Process a payment now</span>
                    </label>
                </div>

                {{-- End date --}}
                <div class="grid grid-cols-1 items-start gap-4 sm:grid-cols-[160px_1fr]">
                    <label class="text-sm font-medium text-slate-900 pt-2.5">End date</label>
                    <div class="space-y-1.5">
                        <div class="relative">
                            <x-heroicon-o-calendar-days class="absolute left-3 top-1/2 size-5 -translate-y-1/2 text-slate-400 pointer-events-none" />
                            <input
                                type="{{ $editEndDate ? 'date' : 'text' }}"
                                onfocus="this.type='date'"
                                onblur="if (! this.value) this.type='text'"
                                wire:model="editEndDate"
                                placeholder="dd/mm/yyyy"
                                class="block w-full rounded-lg border border-slate-300 py-2.5 pl-10 pr-3 text-base text-slate-900 placeholder:text-slate-400 focus:border-teal-500 focus:ring-teal-500"
                            >
                        </div>
                        <p class="text-xs text-slate-500">Leave blank for unlimited.</p>
                    </div>
                </div>

                {{-- Max plan amount --}}
                <div class="grid grid-cols-1 items-center gap-4 sm:grid-cols-[160px_1fr]">
                    <label class="text-sm font-medium text-slate-900">Max plan amount</label>
                    @if ($editHasMaxPlanAmount)
                        <div class="flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 transition focus-within:border-teal-500 focus-within:ring-1 focus-within:ring-teal-500">
                            <span class="text-base font-medium text-slate-900">{{ strtoupper($subscription->currency) }}</span>
                            <input type="number" wire:model="editMaxPlanAmount" step="0.01" min="1" max="99999.99" class="min-w-0 flex-1 border-0 bg-transparent px-1 text-base text-slate-900 outline-none placeholder:text-slate-400">
                        </div>
                    @else
                        <button type="button" wire:click="$set('editHasMaxPlanAmount', true)" class="inline-flex items-center gap-1.5 justify-self-start text-sm font-medium text-teal-600 hover:text-teal-700">
                            <x-heroicon-o-plus class="size-4" />
                            Add limit
                        </button>
                    @endif
                </div>

                {{-- Max plan installments --}}
                <div class="grid grid-cols-1 items-center gap-4 sm:grid-cols-[160px_1fr]">
                    <label class="text-sm font-medium text-slate-900">Max plan installments</label>
                    @if ($editHasMaxPlanInstallments)
                        <input type="number" wire:model="editMaxPlanInstallments" min="1" class="block w-full rounded-lg border border-slate-300 py-2 px-3 text-sm text-slate-900 focus:border-teal-500 focus:ring-teal-500">
                    @else
                        <button type="button" wire:click="$set('editHasMaxPlanInstallments', true)" class="inline-flex items-center gap-1.5 justify-self-start text-sm font-medium text-teal-600 hover:text-teal-700">
                            <x-heroicon-o-plus class="size-4" />
                            Add limit
                        </button>
                    @endif
                </div>

                {{-- Estimated transaction costs --}}
                <div class="grid grid-cols-1 items-center gap-4 sm:grid-cols-[160px_1fr]">
                    <label class="text-sm font-medium text-slate-900">Estimated transaction costs</label>
                    <div class="text-sm font-semibold text-slate-900">{{ strtoupper($subscription->currency) }} {{ number_format($this->transactionCostEstimate, 2) }}</div>
                </div>

                {{-- Cover transaction costs --}}
                <div class="grid grid-cols-1 items-start gap-4 sm:grid-cols-[160px_1fr]">
                    <div></div>
                    <label class="flex items-start gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3 cursor-pointer">
                        <input type="checkbox" wire:model.live="editCoverFee" class="mt-0.5 size-4 rounded border-slate-300 text-teal-600 focus:ring-teal-600">
                        <span class="text-sm font-semibold text-slate-900">Cover transaction costs</span>
                        <x-heroicon-o-question-mark-circle class="mt-0.5 size-4 text-slate-400" />
                    </label>
                </div>

                {{-- Payment method --}}
                <div class="grid grid-cols-1 items-start gap-4 sm:grid-cols-[160px_1fr]">
                    <label class="text-sm font-medium text-slate-900">Payment method</label>
                    <div class="space-y-3">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="radio" wire:model.live="editPaymentMethod" value="existing" class="size-4 border-slate-300 text-teal-600 focus:ring-teal-600">
                            <div class="flex flex-wrap items-center gap-2 text-sm text-slate-900">
                                @php
                                    $existingCardIcon = match (strtolower($this->latestDonation?->payment_method_brand ?? '')) {
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
                                <x-dynamic-component :component="$existingCardIcon" class="size-6" />
                                <span>{{ $this->latestDonation?->payment_method_brand ? ucfirst($this->latestDonation->payment_method_brand) : 'Card' }}</span>
                                @if ($this->latestDonation?->payment_method_last4)
                                    <span class="text-slate-500">•••• {{ $this->latestDonation->payment_method_last4 }}</span>
                                @endif
                            </div>
                        </label>

                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="radio" wire:model.live="editPaymentMethod" value="new" class="size-4 border-slate-300 text-teal-600 focus:ring-teal-600">
                            <span class="text-sm text-slate-900">New credit card</span>
                        </label>

                        @if ($editPaymentMethod === 'new' && $setupIntentClientSecret)
                            <div class="rounded-lg border border-slate-200 p-3">
                                <div id="stripe-payment-element" class="rounded-lg border border-slate-300 p-3"></div>
                                <div x-show="error" class="mt-2 text-xs text-red-600" x-text="error"></div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Next installment summary --}}
                <div class="rounded-lg border border-blue-100 bg-blue-50 px-4 py-3">
                    <p class="text-sm text-slate-700">
                        The next installment of <span class="font-semibold text-slate-900">{{ $this->nextInstallmentDisplay['amount'] }}</span>
                        @if ($editCoverFee)
                            <span class="text-slate-600">(with costs covered)</span>
                        @endif
                        will run on <span class="font-semibold text-slate-900">{{ $this->nextInstallmentDisplay['date'] }}</span>.
                    </p>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <flux:modal.close>
                    <x-ui.button wireClick="closeEditPaymentDetailsModal" variant="secondary">Cancel</x-ui.button>
                </flux:modal.close>
                <button type="button" @click="submit" :disabled="loading" class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800 disabled:opacity-50 disabled:cursor-not-allowed">
                    <span x-show="loading" class="mr-2">Saving...</span>
                    <span>Save changes</span>
                </button>
            </div>
        </div>
    </flux:modal>

    {{-- Recurring Upgrade Link Modal --}}
    <flux:modal wire:model="showUpgradeModal" name="upgrade-amount-modal">
        <div class="space-y-4" x-data="{ copied: false }">
            <div>
                <h3 class="text-lg font-semibold text-slate-900">Recurring amount upgrade link</h3>
                <p class="mt-1 text-sm text-slate-500">Leads to a page offering to increase the recurring plan amount.</p>
            </div>

            <p class="text-sm text-slate-600">Upgrade links let supporters quickly increase their recurring donation or cover transaction fees, helping boost upgrades by 35–65% and increasing average donations by 50–110%.</p>

            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3">
                <p class="text-sm text-amber-800">
                    These upgrade links are unique to each recurring plan and can be used by anyone. Please make sure to send them only to the supporter registered with {{ $subscription->donor->email }} to avoid the wrong supporter making the upgrade.
                </p>
            </div>

            <div class="flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                <span class="flex-1 truncate text-sm text-slate-700 font-mono">{{ $this->upgradeUrl() }}</span>
                <button
                    type="button"
                    @click="navigator.clipboard.writeText('{{ $this->upgradeUrl() }}'); copied = true; setTimeout(() => copied = false, 2000)"
                    class="inline-flex shrink-0 items-center gap-1.5 rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50"
                >
                    <x-heroicon-o-clipboard-document class="size-4" />
                    <span x-show="!copied">Copy</span>
                    <span x-show="copied" x-cloak class="text-emerald-600">Copied!</span>
                </button>
            </div>

            <div class="flex justify-end pt-2">
                <flux:modal.close>
                    <x-ui.button wireClick="closeUpgradeModal" variant="secondary">Close</x-ui.button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>

    {{-- Cancel Recurring Modal --}}
    <flux:modal wire:model="showCancelModal" name="cancel-recurring-modal">
        <div class="space-y-5">
            <div>
                <h3 class="text-lg font-semibold text-slate-900">Cancel recurring</h3>
                <p class="mt-2 text-sm text-slate-600">Ending a recurring plan halts future payments and triggers a cancellation email to the supporter, if enabled.</p>
            </div>

            <flux:select wire:model="cancelReason" label="Cancellation reason" placeholder="Select reason">
                <flux:select.option value="">Select reason</flux:select.option>
                <flux:select.option value="Financial difficulty">Financial difficulty</flux:select.option>
                <flux:select.option value="Life changes">Life changes</flux:select.option>
                <flux:select.option value="Switched donation method">Switched donation method</flux:select.option>
                <flux:select.option value="Change in giving preferences">Change in giving preferences</flux:select.option>
                <flux:select.option value="Subscription consolidation">Subscription consolidation</flux:select.option>
                <flux:select.option value="Loss of confidence in organization">Loss of confidence in organization</flux:select.option>
                <flux:select.option value="Technical error">Technical error</flux:select.option>
                <flux:select.option value="Created by mistake">Created by mistake</flux:select.option>
                <flux:select.option value="Unclear reason">Unclear reason</flux:select.option>
                <flux:select.option value="Other">Other</flux:select.option>
            </flux:select>

            <div class="space-y-2">
                <label class="block text-sm font-semibold text-slate-900">Details <span class="font-normal text-slate-400">(optional)</span></label>
                <flux:textarea wire:model="cancelDetails" rows="3" />
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <flux:modal.close>
                    <x-ui.button wireClick="closeCancelModal" variant="secondary">Keep recurring</x-ui.button>
                </flux:modal.close>
                <x-ui.button wireClick="cancelSubscription" variant="danger">Cancel recurring</x-ui.button>
            </div>
        </div>
    </flux:modal>

    {{-- Skip Installments Modal --}}
    <flux:modal wire:model="showSkipModal" name="skip-installments-modal">
        <div class="space-y-6">
            <div>
                <h3 class="text-lg font-semibold text-slate-900">Skip installments</h3>
            </div>

            <div class="space-y-3">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="radio" wire:model.live="skipDuration" value="1" class="size-4 border-slate-300 text-teal-600 focus:ring-teal-600">
                    <span class="text-sm text-slate-900">Skip for 1 month</span>
                </label>

                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="radio" wire:model.live="skipDuration" value="3" class="size-4 border-slate-300 text-teal-600 focus:ring-teal-600">
                    <span class="text-sm text-slate-900">Skip for 3 month</span>
                </label>

                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="radio" wire:model.live="skipDuration" value="custom" class="size-4 border-slate-300 text-teal-600 focus:ring-teal-600">
                    <span class="text-sm text-slate-900">Skip for 1-12 months</span>
                </label>

                @if ($skipDuration === 'custom')
                    <div class="pl-7">
                        <input type="number" wire:model.live="customSkipMonths" min="1" max="12" class="block w-32 rounded-lg border border-slate-300 py-2 px-3 text-sm text-slate-900 focus:border-teal-500 focus:ring-teal-500">
                    </div>
                @endif
            </div>

            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3">
                <p class="text-sm text-slate-700">
                    The next installment will be made on <span class="font-semibold text-slate-900">{{ myrTime($this->skipNextInstallmentDate) }}</span>
                </p>
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <flux:modal.close>
                    <x-ui.button wireClick="closeSkipModal" variant="secondary">Cancel</x-ui.button>
                </flux:modal.close>
                <x-ui.button wireClick="confirmSkip" variant="primary">Confirm</x-ui.button>
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

    <script>
        function editPaymentDetailsModal() {
            return {
                stripe: null,
                elements: null,
                cardElement: null,
                loading: false,
                error: '',

                init() {
                    this.$watch('$wire.editPaymentMethod', (value) => {
                        if (value === 'new' && this.$wire.setupIntentClientSecret) {
                            this.$nextTick(() => this.mountElements());
                        }
                    });

                    this.$watch('$wire.setupIntentClientSecret', (secret) => {
                        if (secret && this.$wire.editPaymentMethod === 'new') {
                            this.$nextTick(() => this.mountElements());
                        }
                    });
                },

                async loadStripe() {
                    if (window.Stripe) {
                        return;
                    }

                    return new Promise((resolve, reject) => {
                        const script = document.createElement('script');
                        script.src = 'https://js.stripe.com/v3/';
                        script.async = true;
                        script.onload = resolve;
                        script.onerror = () => reject(new Error('Failed to load Stripe'));
                        document.head.appendChild(script);
                    });
                },

                async mountElements() {
                    try {
                        await this.loadStripe();
                    } catch (e) {
                        this.error = 'Stripe failed to load. Please try again.';
                        return;
                    }

                    const container = document.getElementById('stripe-payment-element');
                    if (! container) {
                        return;
                    }

                    const stripeConfig = {};
                    if (this.$wire.setupIntentStripeAccount) {
                        stripeConfig.stripeAccount = this.$wire.setupIntentStripeAccount;
                    }

                    this.stripe = Stripe('{{ config('services.stripe.key') }}', stripeConfig);
                    this.elements = this.stripe.elements({
                        clientSecret: this.$wire.setupIntentClientSecret,
                        appearance: { theme: 'stripe' },
                    });

                    this.cardElement = this.elements.create('payment', {
                        paymentMethodTypes: ['card'],
                        wallets: {
                            link: 'never',
                        },
                    });

                    this.cardElement.mount('#stripe-payment-element');

                    this.cardElement.on('change', (event) => {
                        this.error = event.error ? event.error.message : '';
                    });
                },

                async submit() {
                    if (this.$wire.editPaymentMethod !== 'new') {
                        this.$wire.savePaymentDetails();
                        return;
                    }

                    this.loading = true;
                    this.error = '';

                    const { setupIntent, error } = await this.stripe.confirmSetup({
                        elements: this.elements,
                        redirect: 'if_required',
                    });

                    if (error) {
                        this.error = error.message;
                        this.loading = false;
                        return;
                    }

                    try {
                        await this.$wire.updatePaymentMethodFromJs(setupIntent.payment_method);
                        await this.$wire.savePaymentDetails();
                    } finally {
                        this.loading = false;
                    }
                },
            };
        }
    </script>

    {{-- Email Preview Modal --}}
    <div
        x-cloak
        x-data="{}"
        x-show="$wire.showPreviewModal"
        x-on:keydown.escape.window="$wire.closePreviewModal()"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4"
        x-on:click.self="$wire.closePreviewModal()"
    >
        <div class="flex max-h-[90vh] w-full max-w-3xl flex-col rounded-xl bg-white shadow-xl">
            {{-- Modal header --}}
            <div class="flex items-start justify-between border-b border-slate-200 px-5 py-4">
                <h3 class="text-lg font-semibold text-slate-900">Email</h3>
                <button
                    type="button"
                    x-on:click="$wire.closePreviewModal()"
                    class="ml-4 shrink-0 text-slate-400 transition hover:text-slate-600"
                >
                    <x-heroicon-o-x-mark class="size-5" />
                </button>
            </div>

            {{-- Email metadata --}}
            <div class="border-b border-slate-200 px-5 py-3 text-sm">
                <div class="flex items-baseline gap-3 py-1">
                    <span class="w-16 shrink-0 text-right text-slate-500">From:</span>
                    <span class="text-slate-800">
                        <span x-text="$wire.previewFromName"></span>
                        <span class="text-slate-500" x-show="$wire.previewFromEmail">&lt;<span x-text="$wire.previewFromEmail"></span>&gt;</span>
                    </span>
                    <span class="ml-auto shrink-0 text-slate-400" x-text="$wire.previewSentAt"></span>
                </div>
                <div class="flex items-baseline gap-3 py-1">
                    <span class="w-16 shrink-0 text-right text-slate-500">To:</span>
                    <span class="text-slate-800">
                        {{ $subscription->donor ? $subscription->donor->name : 'Supporter' }}
                        <span class="text-slate-500">&lt;<span x-text="$wire.previewToEmail"></span>&gt;</span>
                    </span>
                    <button
                        type="button"
                        wire:click="resendFromModal"
                        class="ml-auto inline-flex shrink-0 items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-1 text-xs font-medium text-slate-600 transition hover:border-slate-300 hover:bg-slate-50"
                    >
                        <x-heroicon-o-arrow-path class="size-3.5" />
                        Resend
                    </button>
                </div>
                <div class="flex items-baseline gap-3 py-1">
                    <span class="w-16 shrink-0 text-right text-slate-500">Subject:</span>
                    <span class="font-medium text-slate-900" x-text="$wire.previewSubject"></span>
                </div>
            </div>

            {{-- Email body iframe --}}
            <div class="relative flex-1 overflow-hidden bg-slate-100">
                {{-- Loading skeleton --}}
                <div
                    x-show="! $wire.previewHtml"
                    class="absolute inset-0 z-10 flex flex-col gap-4 bg-white p-8"
                >
                    <div class="h-6 w-3/4 animate-pulse rounded bg-slate-200"></div>
                    <div class="h-4 w-1/2 animate-pulse rounded bg-slate-200"></div>
                    <div class="mt-4 space-y-3">
                        <div class="h-4 w-full animate-pulse rounded bg-slate-200"></div>
                        <div class="h-4 w-full animate-pulse rounded bg-slate-200"></div>
                        <div class="h-4 w-5/6 animate-pulse rounded bg-slate-200"></div>
                        <div class="h-4 w-4/5 animate-pulse rounded bg-slate-200"></div>
                    </div>
                    <div class="mt-6 h-32 w-full animate-pulse rounded-lg bg-slate-200"></div>
                    <div class="mt-4 h-4 w-2/3 animate-pulse rounded bg-slate-200"></div>
                </div>

                <iframe
                    x-ref="previewFrame"
                    x-effect="
                        const html = $wire.previewHtml;
                        if (html && $refs.previewFrame) {
                            $refs.previewFrame.srcdoc = html;
                        }
                    "
                    class="h-[60vh] w-full bg-white"
                    :class="{ 'invisible': ! $wire.previewHtml }"
                    title="Email preview"
                    sandbox
                ></iframe>
            </div>
        </div>
    </div>

    {{-- Resend Confirmation Modal --}}
    <div
        x-cloak
        x-data="{}"
        x-show="$wire.showResendModal"
        x-on:keydown.escape.window="$wire.closeResendModal()"
        class="fixed inset-0 z-[100] flex items-start justify-center bg-slate-900/50 p-4 pt-20"
        x-on:click.self="$wire.closeResendModal()"
    >
        <div class="w-full max-w-md rounded-xl bg-white shadow-xl">
            {{-- Modal header --}}
            <div class="flex items-start justify-between border-b border-slate-200 px-5 py-4">
                <h3 class="text-lg font-semibold text-slate-900">Resend email</h3>
                <button
                    type="button"
                    x-on:click="$wire.closeResendModal()"
                    class="ml-4 shrink-0 text-slate-400 transition hover:text-slate-600"
                >
                    <x-heroicon-o-x-mark class="size-5" />
                </button>
            </div>

            {{-- Modal body --}}
            <div class="space-y-4 px-5 py-4">
                <p class="text-sm text-slate-700">
                    This will resend the email to the supporter. Are you sure you want to continue?
                </p>

                <div>
                    <label for="resend-email-address" class="block text-sm font-medium text-slate-700">Email address</label>
                    <input
                        id="resend-email-address"
                        type="email"
                        wire:model="resendRecipientEmail"
                        class="mt-1 block w-full rounded-lg border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-teal-500 focus:ring-teal-500"
                    />
                </div>
            </div>

            {{-- Modal footer --}}
            <div class="flex justify-end gap-3 border-t border-slate-200 px-5 py-4">
                <button
                    type="button"
                    x-on:click="$wire.closeResendModal()"
                    wire:loading.attr="disabled"
                    wire:target="resendConfirmed"
                    class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 disabled:opacity-50"
                >
                    Cancel
                </button>
                <button
                    type="button"
                    wire:click="resendConfirmed"
                    wire:loading.attr="disabled"
                    wire:target="resendConfirmed"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800 disabled:opacity-50"
                >
                    <x-heroicon-o-arrow-path class="size-4 animate-spin" wire:loading wire:target="resendConfirmed" />
                    <span wire:loading.remove wire:target="resendConfirmed">Resend</span>
                    <span wire:loading wire:target="resendConfirmed">Resending...</span>
                </button>
            </div>
        </div>
    </div>
</div>
