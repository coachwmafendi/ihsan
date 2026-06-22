{{-- resources/views/livewire/app/donations/show.blade.php --}}
<div
    class="space-y-6"
    x-data="{
        copied: false,
        activeSection: 'section-donation',
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
    <div>
        <h1 class="text-3xl font-bold tracking-tight text-slate-900">{{ $this->formattedOriginalAmount() }} donation</h1>
        <p class="mt-1 flex items-center gap-2 text-sm text-slate-500">
            <span>ID {{ $donation->public_id }}</span>
            <x-ui.tooltip text="Copy donation ID">
                <button
                    x-on:click="navigator.clipboard.writeText('{{ $donation->public_id }}'); copied = true; setTimeout(() => copied = false, 2000)"
                    class="inline-flex items-center rounded p-0.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600"
                >
                    <x-heroicon-o-clipboard-document class="size-3.5" />
                </button>
            </x-ui.tooltip>
            <span x-show="copied" x-transition class="text-xs text-emerald-600">Copied!</span>
            @if ($donation->currency !== 'myr' && $donation->base_amount !== null)
                <span>· ≈ {{ $this->formattedBaseAmount() }}</span>
            @endif
        </p>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-[1fr_18rem] xl:grid-cols-[1fr_20rem]">
        {{-- Left Column --}}
        <div class="space-y-6">
            {{-- Donation --}}
            <section id="section-donation" data-section="section-donation">
                <x-ui.card title="Donation" icon="heroicon-o-banknotes">
                    <x-slot:actions>
                        <button type="button" wire:click="openEditDonationModal" class="text-sm font-medium text-blue-600 hover:text-blue-700">
                            Edit
                        </button>
                    </x-slot:actions>

                    <dl class="space-y-5">
                        <div class="grid grid-cols-1 gap-1 sm:grid-cols-[180px_1fr] sm:gap-6">
                            <dt class="text-sm text-slate-500">Donation amount</dt>
                            <dd class="text-sm font-medium text-slate-900">{!! $this->donationAmountDisplay() !!}</dd>
                        </div>
                        <div class="grid grid-cols-1 gap-1 sm:grid-cols-[180px_1fr] sm:gap-6">
                            <dt class="text-sm text-slate-500">Donation ID</dt>
                            <dd class="flex items-center gap-2 text-sm font-medium text-slate-900">
                                {{ $donation->public_id }}
                                <x-ui.tooltip text="Copy donation ID">
                                    <button
                                        x-on:click="navigator.clipboard.writeText('{{ $donation->public_id }}'); copied = true; setTimeout(() => copied = false, 2000)"
                                        class="inline-flex items-center rounded p-0.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600"
                                    >
                                        <x-heroicon-o-clipboard-document class="size-3.5" />
                                    </button>
                                </x-ui.tooltip>
                            </dd>
                        </div>
                        <div class="grid grid-cols-1 gap-1 sm:grid-cols-[180px_1fr] sm:gap-6">
                            <dt class="text-sm text-slate-500">Supporter</dt>
                            <dd class="text-sm font-medium">
                                @if ($donation->donor)
                                    <a href="/app/supporters/{{ $donation->donor->public_id }}" wire:navigate class="text-blue-600 hover:text-blue-700">
                                        {{ $donation->donor->name }}
                                    </a>
                                @else
                                    <span class="text-slate-900">Anonymous</span>
                                @endif
                            </dd>
                        </div>
                        <div class="grid grid-cols-1 gap-1 sm:grid-cols-[180px_1fr] sm:gap-6">
                            <dt class="text-sm text-slate-500">Campaign</dt>
                            <dd class="text-sm font-medium">
                                @if ($donation->campaign)
                                    <a href="{{ route('app.campaigns.edit', $donation->campaign) }}" wire:navigate class="text-blue-600 hover:text-blue-700">
                                        {{ $donation->campaign->title }}
                                    </a>
                                @else
                                    <span class="text-slate-500">No campaign linked</span>
                                @endif
                            </dd>
                        </div>
                        <div class="grid grid-cols-1 gap-1 sm:grid-cols-[180px_1fr] sm:gap-6">
                            <dt class="text-sm text-slate-500">Donation date</dt>
                            <dd class="text-sm text-slate-900">{{ myrTime($donation->created_at) }}</dd>
                        </div>
                        <div class="grid grid-cols-1 gap-1 sm:grid-cols-[180px_1fr] sm:gap-6">
                            <dt class="text-sm text-slate-500">Success date</dt>
                            <dd class="text-sm text-slate-900">{{ $this->successDate() ?? '—' }}</dd>
                        </div>
                        <div class="grid grid-cols-1 gap-1 sm:grid-cols-[180px_1fr] sm:gap-6">
                            <dt class="text-sm text-slate-500">Frequency</dt>
                            <dd class="text-sm text-slate-900">{{ $this->frequencyLabel() }}</dd>
                        </div>
                    </dl>
                </x-ui.card>
            </section>

            {{-- Payment & Fees --}}
            <section id="section-payment" data-section="section-payment">
                <x-ui.card title="Payment & fees" icon="heroicon-o-credit-card">
                    <dl class="space-y-5">
                        <div class="grid grid-cols-1 gap-1 sm:grid-cols-[180px_1fr] sm:gap-6">
                            <dt class="text-sm text-slate-500">Payment amount</dt>
                            <dd class="text-sm font-medium text-slate-900">
                                {{ $this->formattedOriginalAmount() }}
                                @if ($donation->currency !== 'myr' && $donation->base_amount !== null)
                                    <span class="text-slate-400">≈ {{ $this->formattedBaseAmount() }}</span>
                                @endif
                            </dd>
                        </div>
                        <div class="grid grid-cols-1 gap-1 sm:grid-cols-[180px_1fr] sm:gap-6">
                            <dt class="text-sm text-slate-500">Before fees covered</dt>
                            <dd class="text-sm font-medium text-slate-900">
                                {{ $this->beforeFeesCovered() }}
                                @if ($donation->currency !== 'myr' && $donation->base_amount !== null)
                                    <span class="text-slate-400">≈ {{ $this->beforeFeesCoveredBase() }}</span>
                                @endif
                            </dd>
                        </div>
                        <div class="grid grid-cols-1 gap-1 sm:grid-cols-[180px_1fr] sm:gap-6">
                            <dt class="text-sm text-slate-500">Processing fee</dt>
                            <dd class="text-sm font-medium text-slate-900">
                                {{ $this->platformFee() }}
                                @if ($donation->currency !== 'myr' && $donation->base_amount !== null)
                                    <span class="text-slate-400">≈ {{ $this->platformFeeBase() }}</span>
                                @endif
                                <x-ui.tooltip>
                                    <x-heroicon-o-question-mark-circle class="inline-block size-4 text-slate-400" />
                                    <x-slot:tip>Processing <strong>fee</strong> charged by Ihsan.</x-slot:tip>
                                </x-ui.tooltip>
                            </dd>
                        </div>
                        <div class="grid grid-cols-1 gap-1 sm:grid-cols-[180px_1fr] sm:gap-6">
                            <dt class="text-sm text-slate-500">Stripe fee</dt>
                            <dd class="text-sm font-medium text-slate-900">{{ $this->paymentProcessingFee() }}</dd>
                        </div>
                        <div class="grid grid-cols-1 gap-1 sm:grid-cols-[180px_1fr] sm:gap-6">
                            <dt class="text-sm text-slate-500">Payout amount</dt>
                            <dd class="text-sm font-medium text-slate-900">
                                {{ $this->payoutAmount() }}
                                <x-ui.tooltip text="Net amount received after fees">
                                    <x-heroicon-o-question-mark-circle class="inline-block size-4 text-slate-400" />
                                </x-ui.tooltip>
                            </dd>
                        </div>
                        <div class="grid grid-cols-1 gap-1 sm:grid-cols-[180px_1fr] sm:gap-6">
                            <dt class="text-sm text-slate-500">Fee covered</dt>
                            <dd>
                                @if ((float) $donation->donor_fee_covered > 0)
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">
                                        <x-heroicon-o-check-circle class="size-4" />
                                        Covered
                                    </span>
                                @else
                                    <span class="text-sm text-slate-900">Not covered</span>
                                @endif
                            </dd>
                        </div>
                        <div class="grid grid-cols-1 gap-1 sm:grid-cols-[180px_1fr] sm:gap-6">
                            <dt class="text-sm text-slate-500">Effective fee</dt>
                            <dd class="text-sm font-medium text-slate-900">{{ $this->effectiveFeeRate() }}</dd>
                        </div>
                        <div class="grid grid-cols-1 gap-1 sm:grid-cols-[180px_1fr] sm:gap-6">
                            <dt class="text-sm text-slate-500">Payment ID</dt>
                            <dd class="text-sm font-medium">
                                @if ($donation->stripe_charge_id)
                                    <a
                                        href="https://dashboard.stripe.com/payments/{{ $donation->stripe_charge_id }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="inline-flex items-center gap-1 text-blue-600 hover:text-blue-700"
                                    >
                                        {{ $donation->stripe_charge_id }}
                                        <x-heroicon-o-arrow-top-right-on-square class="size-4" />
                                    </a>
                                @else
                                    <span class="text-slate-500">—</span>
                                @endif
                            </dd>
                        </div>
                        <div class="grid grid-cols-1 gap-1 sm:grid-cols-[180px_1fr] sm:gap-6">
                            <dt class="text-sm text-slate-500">Payment processor</dt>
                            <dd class="text-sm font-medium text-slate-900">Stripe</dd>
                        </div>
                        <div class="grid grid-cols-1 gap-1 sm:grid-cols-[180px_1fr] sm:gap-6">
                            <dt class="text-sm text-slate-500">Payment method</dt>
                            <dd class="flex items-center gap-2 text-sm font-medium text-slate-900">
                                @php
                                    $pmType = strtolower($donation->payment_method_type ?? '');
                                    $pmBrand = strtolower($donation->payment_method_brand ?? '');

                                    $paymentLabel = match (true) {
                                        $pmType === 'apple_pay' || $pmBrand === 'apple_pay' => 'Apple Pay',
                                        $pmType === 'google_pay' || $pmBrand === 'google_pay' => 'Google Pay',
                                        $pmBrand !== '' => \Illuminate\Support\Str::headline($donation->payment_method_brand),
                                        $pmType !== '' => \Illuminate\Support\Str::headline($donation->payment_method_type),
                                        default => 'Card',
                                    };
                                @endphp
                                <x-heroicon-o-credit-card class="size-5 text-slate-500" />
                                {{ $paymentLabel }}
                            </dd>
                        </div>
                        @if ($donation->payment_method_brand || $donation->payment_method_last4)
                            <div class="grid grid-cols-1 gap-1 sm:grid-cols-[180px_1fr] sm:gap-6">
                                <dt class="text-sm text-slate-500">Credit card</dt>
                                <dd class="flex items-center gap-2 text-sm font-medium text-slate-900">
                                    @php
                                        $cardIcon = match (strtolower($donation->payment_method_brand ?? '')) {
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
                                    <x-dynamic-component :component="$cardIcon" class="size-8" />
                                    {{ $donation->payment_method_brand ? \Illuminate\Support\Str::headline($donation->payment_method_brand) : 'Card' }}
                                    @if ($donation->payment_method_last4)
                                        <span class="text-slate-500">•••• {{ $donation->payment_method_last4 }}</span>
                                    @endif
                                </dd>
                            </div>
                        @endif
                    </dl>
                </x-ui.card>
            </section>

            {{-- Recurring Plan --}}
            @if ($donation->subscription)
                <section id="section-recurring" data-section="section-recurring">
                    <x-ui.card title="Recurring plan" icon="heroicon-o-arrow-path">
                        <dl class="space-y-5">
                            <div class="grid grid-cols-1 gap-1 sm:grid-cols-[180px_1fr] sm:gap-6">
                                <dt class="text-sm text-slate-500">Recurring plan ID</dt>
                                <dd class="text-sm font-medium">
                                    <a href="{{ route('app.subscriptions.show', $donation->subscription) }}" wire:navigate class="text-blue-600 hover:text-blue-700">
                                        {{ $donation->subscription->public_id }}
                                    </a>
                                </dd>
                            </div>
                            <div class="grid grid-cols-1 gap-1 sm:grid-cols-[180px_1fr] sm:gap-6">
                                <dt class="text-sm text-slate-500">Status</dt>
                                <dd class="text-sm font-medium text-slate-900">{{ ucfirst($donation->subscription->status->value) }}</dd>
                            </div>
                            <div class="grid grid-cols-1 gap-1 sm:grid-cols-[180px_1fr] sm:gap-6">
                                <dt class="text-sm text-slate-500">Total</dt>
                                <dd class="text-sm font-medium text-slate-900">{{ $this->subscriptionTotal() }}</dd>
                            </div>
                            <div class="grid grid-cols-1 gap-1 sm:grid-cols-[180px_1fr] sm:gap-6">
                                <dt class="text-sm text-slate-500">Installments</dt>
                                <dd class="text-sm font-medium text-slate-900">{{ $donation->subscription->payment_count }}</dd>
                            </div>
                            <div class="grid grid-cols-1 gap-1 sm:grid-cols-[180px_1fr] sm:gap-6">
                                <dt class="text-sm text-slate-500">Create date</dt>
                                <dd class="text-sm text-slate-900">{{ myrTime($donation->subscription->created_at) }}</dd>
                            </div>
                            <div class="grid grid-cols-1 gap-1 sm:grid-cols-[180px_1fr] sm:gap-6">
                                <dt class="text-sm text-slate-500">Previous installment</dt>
                                <dd class="text-sm text-slate-900">{{ $this->subscriptionPreviousInstallment() ?? '—' }}</dd>
                            </div>
                            <div class="grid grid-cols-1 gap-1 sm:grid-cols-[180px_1fr] sm:gap-6">
                                <dt class="text-sm text-slate-500">Next installment</dt>
                                <dd class="text-sm text-slate-900">{{ $this->subscriptionNextInstallment() ?? '—' }}</dd>
                            </div>
                        </dl>
                    </x-ui.card>
                </section>
            @endif

            {{-- Personal Information --}}
            <section id="section-personal" data-section="section-personal">
                <x-ui.card title="Personal information" icon="heroicon-o-user">
                    <x-slot:actions>
                        @if ($donation->donor)
                            <button type="button" wire:click="openEditPersonalModal" class="text-sm font-medium text-blue-600 hover:text-blue-700">
                                Edit
                            </button>
                        @endif
                    </x-slot:actions>

                    @if ($donation->donor)
                        <dl class="space-y-5">
                            <div class="grid grid-cols-1 gap-1 sm:grid-cols-[180px_1fr] sm:gap-6">
                                <dt class="text-sm text-slate-500">Name</dt>
                                <dd class="text-sm font-medium text-slate-900">{{ $donation->donor->name }}</dd>
                            </div>
                            <div class="grid grid-cols-1 gap-1 sm:grid-cols-[180px_1fr] sm:gap-6">
                                <dt class="text-sm text-slate-500">Email</dt>
                                <dd class="text-sm font-medium text-slate-900">{{ $donation->donor->email }}</dd>
                            </div>
                        </dl>
                    @else
                        <p class="text-sm text-slate-500">Anonymous donation</p>
                    @endif
                </x-ui.card>
            </section>

            {{-- Source --}}
            <section id="section-source" data-section="section-source">
                <x-ui.card title="Source" icon="heroicon-o-arrow-top-right-on-square">
                    <dl class="space-y-5">
                        <div class="grid grid-cols-1 gap-1 sm:grid-cols-[180px_1fr] sm:gap-6">
                            <dt class="text-sm text-slate-500">Source</dt>
                            <dd class="text-sm font-medium text-slate-900">{{ $donation->source_label }}</dd>
                        </div>
                        <div class="grid grid-cols-1 gap-1 sm:grid-cols-[180px_1fr] sm:gap-6">
                            <dt class="text-sm text-slate-500">URL</dt>
                            <dd class="text-sm font-medium">
                                @if ($donation->page_url)
                                    <a href="{{ $donation->page_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 text-blue-600 hover:text-blue-700">
                                        {{ $donation->page_url }}
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
                                @if ($element = $donation->element)
                                    <a href="{{ route('app.elements.edit', $element) }}" class="inline-flex items-center gap-1 text-blue-600 hover:text-blue-700">
                                        <x-heroicon-o-squares-2x2 class="size-4" />
                                        {{ $donation->element_label }}
                                    </a>
                                @elseif ($donation->element_label)
                                    <span class="inline-flex items-center gap-1 text-slate-700">
                                        <x-heroicon-o-squares-2x2 class="size-4" />
                                        {{ $donation->element_label }}
                                    </span>
                                @else
                                    <span class="text-slate-500">—</span>
                                @endif
                            </dd>
                        </div>
                    </dl>
                </x-ui.card>
            </section>

            {{-- Insights --}}
            <section id="section-insights" data-section="section-insights">
                <x-ui.card title="Insights" icon="heroicon-o-arrow-trending-up">
                    <dl class="space-y-5">
                        <div class="grid grid-cols-1 gap-1 sm:grid-cols-[180px_1fr] sm:gap-6">
                            <dt class="text-sm text-slate-500">IP address</dt>
                            <dd class="text-sm font-medium text-slate-900">{{ $donation->ip_address ?? '—' }}</dd>
                        </div>
                        <div class="grid grid-cols-1 gap-1 sm:grid-cols-[180px_1fr] sm:gap-6">
                            <dt class="text-sm text-slate-500">IP geolocation</dt>
                            <dd class="text-sm font-medium text-slate-900">
                                {{ collect([$donation->geo_city, $donation->geo_region])->filter()->join(', ') ?: '—' }}
                            </dd>
                        </div>
                        <div class="grid grid-cols-1 gap-1 sm:grid-cols-[180px_1fr] sm:gap-6">
                            <dt class="text-sm text-slate-500">Browser</dt>
                            <dd class="text-sm font-medium text-slate-900">{{ $donation->browser ?? '—' }}</dd>
                        </div>
                        <div class="grid grid-cols-1 gap-1 sm:grid-cols-[180px_1fr] sm:gap-6">
                            <dt class="text-sm text-slate-500">Device</dt>
                            <dd class="text-sm font-medium text-slate-900">{{ $donation->device_type ?? '—' }}</dd>
                        </div>
                        <div class="grid grid-cols-1 gap-1 sm:grid-cols-[180px_1fr] sm:gap-6">
                            <dt class="text-sm text-slate-500">OS</dt>
                            <dd class="text-sm font-medium text-slate-900">{{ $donation->os ?? '—' }}</dd>
                        </div>
                    </dl>
                </x-ui.card>
            </section>

            {{-- Receipts --}}
            <section id="section-receipts" data-section="section-receipts">
                <x-ui.card title="Receipts" icon="heroicon-o-receipt-percent">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-left text-sm">
                            <thead>
                                <tr class="border-b border-slate-100">
                                    <th class="py-2 pr-4 font-medium text-slate-900">Receipt Number</th>
                                    <th class="py-2 pr-4 font-medium text-slate-900">Amount</th>
                                    <th class="py-2 pr-4 font-medium text-slate-900">Donation Date</th>
                                    <th class="py-2 pr-4 font-medium text-slate-900">Issue Date</th>
                                    <th class="py-2"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="border-b border-slate-50 last:border-0">
                                    <td class="py-3 pr-4">
                                        <div class="flex items-center gap-2">
                                            <x-heroicon-o-check-circle class="size-5 text-emerald-500" />
                                            @if ($donation->status->value === 'succeeded')
                                                    <a href="{{ route('donations.receipt.download', $donation) }}" class="font-medium text-slate-900 hover:text-blue-600">
                                                    {{ $donation->invoice_number ?? $donation->public_id }}
                                                </a>
                                            @else
                                                <span class="font-medium text-slate-900">{{ $donation->invoice_number ?? $donation->public_id }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="py-3 pr-4 font-medium text-slate-900">
                                        @if ($donation->currency !== 'myr' && $donation->base_amount !== null)
                                            ≈ {{ $this->formattedBaseAmount() }}
                                        @else
                                            {{ $this->formattedBaseAmount() }}
                                        @endif
                                    </td>
                                    <td class="py-3 pr-4 text-slate-600">{{ myrTime($donation->created_at, withLabel: false, format: 'M d, Y') }}</td>
                                    <td class="py-3 pr-4 text-slate-600">{{ $donation->receipt_sent_at ? myrTime($donation->receipt_sent_at, withLabel: false, format: 'M d, Y') : myrTime($donation->created_at, withLabel: false, format: 'M d, Y') }}</td>
                                    <td class="py-3 text-right">
                                        @if ($donation->status->value === 'succeeded')
                                            <a href="{{ route('donations.receipt.download', $donation) }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-600 hover:text-slate-900">
                                                <x-heroicon-o-arrow-down-tray class="size-4" />
                                                Download
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
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
                            description="Emails sent for this donation will appear here."
                        />
                    @endif
                </x-ui.card>
            </section>
        </div>

        {{-- Right Column / Floating Menu --}}
        <div class="space-y-4">
            <div class="lg:sticky lg:top-6 lg:self-start">
                {{-- Actions --}}
                <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                    @if ($donation->status->value === 'succeeded')
                        <a
                            href="{{ route('donations.receipt.download', $donation) }}"
                            class="flex items-center gap-3 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                        >
                            <x-heroicon-o-arrow-down-tray class="size-5 text-slate-500" />
                            Download receipt
                        </a>
                    @endif
                    @if ($this->canRefund())
                        <button
                            type="button"
                            wire:click="openRefundModal"
                            class="flex w-full items-center gap-3 border-t border-slate-100 px-4 py-3 text-left text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                        >
                            <x-heroicon-o-arrow-uturn-left class="size-5 text-slate-500" />
                            Refund donation
                        </button>
                    @endif
                </div>

                {{-- Navigation --}}
                <div class="mt-4 overflow-hidden rounded-xl border border-slate-200 bg-white p-1.5">
                    <nav class="space-y-0.5" aria-label="Donation sections">
                        <button
                            type="button"
                            @click="scrollToSection('section-donation')"
                            :class="activeSection === 'section-donation' ? 'bg-slate-100 text-slate-900' : 'text-slate-600 hover:bg-slate-50'"
                            class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left text-sm font-semibold transition"
                        >
                            <x-heroicon-o-banknotes class="size-5" />
                            Donation
                        </button>
                        <button
                            type="button"
                            @click="scrollToSection('section-payment')"
                            :class="activeSection === 'section-payment' ? 'bg-slate-100 text-slate-900' : 'text-slate-600 hover:bg-slate-50'"
                            class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left text-sm font-semibold transition"
                        >
                            <x-heroicon-o-credit-card class="size-5" />
                            Payment & fees
                        </button>
                        @if ($donation->subscription)
                            <button
                                type="button"
                                @click="scrollToSection('section-recurring')"
                                :class="activeSection === 'section-recurring' ? 'bg-slate-100 text-slate-900' : 'text-slate-600 hover:bg-slate-50'"
                                class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left text-sm font-semibold transition"
                            >
                                <x-heroicon-o-arrow-path class="size-5" />
                                Recurring plan
                            </button>
                        @endif
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
                            @click="scrollToSection('section-insights')"
                            :class="activeSection === 'section-insights' ? 'bg-slate-100 text-slate-900' : 'text-slate-600 hover:bg-slate-50'"
                            class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left text-sm font-semibold transition"
                        >
                            <x-heroicon-o-arrow-trending-up class="size-5" />
                            Insights
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

    {{-- Back Link --}}
    <div>
        <a href="{{ route('app.donations.index') }}" wire:navigate class="inline-flex items-center text-sm font-medium text-teal-600 hover:text-teal-700">
            <x-heroicon-o-arrow-left class="size-4 mr-1" />
            Back to donations
        </a>
    </div>

    {{-- Refund Modal --}}
    <flux:modal wire:model="showRefundModal" name="refund-donation-modal">
        <div class="space-y-4">
            <h3 class="text-lg font-semibold text-slate-900">Refund donation</h3>

            <p class="text-sm text-slate-600">
                Are you sure you want to refund 100% of the donation to the supporter?
            </p>

            <flux:select wire:model="refundReason" label="Refund reason" placeholder="Select reason">
                <flux:select.option value="duplicate">Duplicate donation</flux:select.option>
                <flux:select.option value="fraud">Fraud</flux:select.option>
                <flux:select.option value="requested_by_supporter">Requested by supporter</flux:select.option>
                <flux:select.option value="other">Other</flux:select.option>
            </flux:select>

            <div class="flex justify-end gap-3 pt-2">
                <flux:modal.close>
                    <x-ui.button wireClick="cancelRefund" variant="secondary">Cancel</x-ui.button>
                </flux:modal.close>
                <x-ui.button wireClick="confirmRefund" variant="danger">Refund donation</x-ui.button>
            </div>
        </div>
    </flux:modal>

    {{-- Edit Donation Modal --}}
    <flux:modal wire:model="showEditDonationModal" name="edit-donation-modal">
        <div class="space-y-4">
            <h3 class="text-lg font-semibold text-slate-900">Edit donation</h3>

            <p class="text-sm text-slate-600">
                These changes will only apply to this specific donation.
            </p>

            <flux:select wire:model="editCampaignId" label="Campaign">
                @php
                    $campaigns = $donation->campaign?->organization?->campaigns ?? collect();
                @endphp
                @foreach ($campaigns as $campaign)
                    <flux:select.option value="{{ $campaign->id }}">{{ $campaign->title }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="flex justify-end gap-3 pt-2">
                <flux:modal.close>
                    <x-ui.button wireClick="cancelEditDonationModal" variant="secondary">Cancel</x-ui.button>
                </flux:modal.close>
                <x-ui.button wireClick="saveDonation" variant="primary">Save changes</x-ui.button>
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

            <flux:checkbox wire:model="editIsAnonymous" label="Anonymous donation" />

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
                    <x-ui.button wireClick="cancelEditPersonalModal" variant="secondary">Cancel</x-ui.button>
                </flux:modal.close>
                <x-ui.button wireClick="savePersonalInformation" variant="primary">Save changes</x-ui.button>
            </div>
        </div>
    </flux:modal>

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
                        {{ $donation->donor ? \Illuminate\Support\Str::title($donation->donor->name) : 'Supporter' }}
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
