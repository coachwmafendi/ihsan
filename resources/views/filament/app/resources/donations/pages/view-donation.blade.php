<x-filament-panels::page>
<div
    x-data="{
        activeSection: 'general',
        scrollTo(id) {
            const el = document.getElementById(id);
            if (el) {
                const top = el.getBoundingClientRect().top + window.scrollY - 96;
                window.scrollTo({ top, behavior: 'smooth' });
            }
        }
    }"
    x-init="$nextTick(() => {
        setTimeout(() => {
            const sections = $el.querySelectorAll('section[id]');
            const updateActive = () => {
                const threshold = window.scrollY + 160;
                let current = sections[0]?.id || 'general';
                for (const section of sections) {
                    if (section.offsetTop <= threshold) {
                        current = section.id;
                    } else {
                        break;
                    }
                }
                activeSection = current;
            };
            window.addEventListener('scroll', updateActive, { passive: true });
            updateActive();
        }, 300);
    })"
    class="flex gap-6"
>
    {{-- Main Content --}}
    <div class="flex-1 min-w-0 space-y-6 pb-4">

        {{-- Donation --}}
        <section id="general" class="scroll-mt-24">
            <x-filament::section icon="heroicon-o-information-circle">
                <x-slot name="heading">Donation</x-slot>

                <div class="space-y-1.5">
                    <x-ui.detail-row label="Donation Amount" label-width="120px">
                        @if ($this->record->currency !== 'myr' && $this->record->base_amount)
                            {{ strtoupper($this->record->currency) }} {{ number_format((float) $this->record->gross_amount, 2) }}
                            <span class="text-gray-400 dark:text-gray-500 ml-1.5">≈ MYR {{ number_format((float) $this->record->base_amount, 2) }}</span>
                        @else
                            {{ strtoupper($this->record->currency) }} {{ number_format((float) $this->record->gross_amount, 2) }}
                        @endif
                    </x-ui.detail-row>

                    <x-ui.detail-row label="Donation ID" label-width="120px" :copyable="true" :value="$this->record->public_id" />

                    <x-ui.detail-row label="Status" label-width="120px">
                        <x-ui.status-badge status="{{ $this->record->status->value }}">
                            {{ ucfirst($this->record->status->value) }}
                        </x-ui.status-badge>
                    </x-ui.detail-row>

                    <x-ui.detail-row label="Supporter" label-width="120px">
                        @if ($this->record->donor)
                            <a href="{{ route('filament.app.resources.supporters.view', $this->record->donor->public_id) }}"
                               class="text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 transition-colors">
                                {{ $this->record->donor->name }}
                            </a>
                        @else
                            —
                        @endif
                    </x-ui.detail-row>

                    <x-ui.detail-row label="Campaign" label-width="120px" :value="$this->record->campaign?->title ?? '—'" />
                    <x-ui.detail-row label="Donation Date" label-width="120px" :value="$this->record->created_at->format('d M Y, h:i A')" />
                    <x-ui.detail-row label="Success Date" label-width="120px">
                        @if ($this->record->status->value === 'succeeded')
                            {{ ($this->record->receipt_sent_at ?? $this->record->updated_at)?->format('d M Y, h:i A') ?? '—' }}
                        @else
                            —
                        @endif
                    </x-ui.detail-row>
                    <x-ui.detail-row label="Frequency" label-width="120px">
                        @if ($this->record->subscription)
                            {{ str($this->record->subscription->interval->value)->headline() }}
                        @else
                            {{ str($this->record->type->value)->headline() }}
                        @endif
                    </x-ui.detail-row>
                </div>
            </x-filament::section>
        </section>

        {{-- Payment & Fee --}}
        <section id="payment-fee" class="scroll-mt-24">
            @php
                $curr = strtoupper($this->record->currency);
                $isForeign = $this->record->currency !== 'myr' && $this->record->base_amount;
                $exchangeRate = $isForeign ? (float) ($this->record->exchange_rate ?? 1) : 1;

                $gross = (float) $this->record->gross_amount;
                $feeCovered = (float) ($this->record->donor_fee_covered ?? 0);
                $beforeFeesCovered = $gross - $feeCovered;

                // Fees are stored in MYR — convert to donation currency for foreign donations
                $feesSettled = $this->record->stripe_fee !== null;
                $stripeFee = ($feesSettled && $exchangeRate > 0) ? (float) $this->record->stripe_fee / $exchangeRate : 0;
                $processingFee = $exchangeRate > 0 ? (float) ($this->record->processing_fee ?? 0) / $exchangeRate : 0;
                $totalFees = $stripeFee + $processingFee;

                // net_amount stored in MYR — convert for foreign donations
                $payout = $exchangeRate > 0 ? (float) $this->record->net_amount / $exchangeRate : (float) $this->record->net_amount;

                $effectiveFeeRate = $gross > 0 ? ($totalFees / $gross * 100) : 0;
            @endphp
            <x-filament::section icon="heroicon-o-banknotes">
                <x-slot name="heading">Payment & Fees</x-slot>
                {{-- Poll every 3s until Stripe fee webhook settles --}}
                @if (!$feesSettled)
                    <div wire:poll.3s="refreshFeeData" class="hidden"></div>
                @endif
                <div class="space-y-1.5">
                    <x-ui.detail-row label="Payment Amount" label-width="180px">
                        {{ $curr }} {{ number_format($gross, 2) }}
                        @if ($isForeign)
                            <span class="text-gray-400 dark:text-gray-500 ml-1.5">≈ MYR {{ number_format((float) $this->record->base_amount, 2) }}</span>
                        @endif
                    </x-ui.detail-row>
                    <x-ui.detail-row label="Before Fees Covered" label-width="180px" :value="$curr.' '.number_format($beforeFeesCovered, 2)" />
                    <x-ui.detail-row label="Stripe Fee" label-width="180px">
                        @if ($feesSettled)
                            {{ $curr }} {{ number_format($stripeFee, 2) }}
                        @else
                            <span class="inline-flex items-center gap-1.5 text-gray-400 dark:text-gray-500 text-sm">
                                <svg class="animate-spin size-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                Syncing…
                            </span>
                        @endif
                    </x-ui.detail-row>
                    <x-ui.detail-row label="Platform Fee" label-width="180px" :value="$curr.' 0.00'" />
                    <x-ui.detail-row label="Processing Fee" label-width="180px" :value="$curr.' '.number_format($processingFee, 2)" />
                    <x-ui.detail-row label="Total Fees" label-width="180px">
                        @if ($feesSettled)
                            {{ $curr }} {{ number_format($totalFees, 2) }}
                        @else
                            <span class="h-4 w-16 rounded bg-gray-100 dark:bg-gray-800 animate-pulse inline-block"></span>
                        @endif
                    </x-ui.detail-row>
                    <x-ui.detail-row label="Payout Amount" label-width="180px">
                        <span class="font-semibold">{{ $curr }} {{ number_format($payout, 2) }}</span>
                    </x-ui.detail-row>
                    <x-ui.detail-row label="Fee Covered" label-width="180px">
                        @if ($feeCovered > 0)
                            <x-ui.status-badge status="success" size="sm">
                                {{ $curr }} {{ number_format($feeCovered, 2) }}
                            </x-ui.status-badge>
                        @else
                            No
                        @endif
                    </x-ui.detail-row>
                    <x-ui.detail-row label="Effective Fee" label-width="180px" :value="number_format($effectiveFeeRate, 2).'%'" />

                    <div class="border-t border-gray-100 dark:border-gray-800 my-2"></div>

                    <x-ui.detail-row label="Payment ID" label-width="180px">
                        <span class="font-mono">{{ $this->record->stripe_payment_intent_id ?? '—' }}</span>
                        @if ($this->record->stripe_payment_intent_id)
                            <x-ui.copy-button :value="$this->record->stripe_payment_intent_id" size="sm" />
                        @endif
                    </x-ui.detail-row>

                    <x-ui.detail-row label="Transaction ID" label-width="180px">
                        @if ($this->record->stripe_charge_id)
                            @php
                                $stripeAccountId = $this->record->campaign?->organization?->stripe_account_id;
                                $chargeUrl = $stripeAccountId
                                    ? 'https://dashboard.stripe.com/connect/accounts/'.$stripeAccountId.'/charges/'.$this->record->stripe_charge_id
                                    : 'https://dashboard.stripe.com/charges/'.$this->record->stripe_charge_id;
                            @endphp
                            <a href="{{ $chargeUrl }}" target="_blank" rel="noopener"
                               class="inline-flex items-center gap-1.5 font-mono text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 transition-colors">
                                {{ $this->record->stripe_charge_id }}
                                <x-heroicon-o-arrow-top-right-on-square class="size-3.5 shrink-0" />
                            </a>
                        @else
                            —
                        @endif
                    </x-ui.detail-row>

                    <x-ui.detail-row label="Payment Processor" label-width="180px">
                        <span class="flex items-center gap-2">
                            <x-icons.stripe class="h-5 w-auto rounded" />
                            Stripe
                        </span>
                    </x-ui.detail-row>

                    <x-ui.detail-row label="Payment Method" label-width="180px"
                        :value="$this->record->payment_method_type ? str($this->record->payment_method_type)->headline()->toString() : '—'" />

                    <x-ui.detail-row label="Credit Card" label-width="180px">
                        @php
                            $brand = strtolower($this->record->payment_method_brand ?? '');
                            $iconMap = ['visa'=>'icons.visa','mastercard'=>'icons.mastercard','amex'=>'icons.amex','american express'=>'icons.amex','discover'=>'icons.discover','jcb'=>'icons.jcb','diners'=>'icons.diners','diners club'=>'icons.diners','unionpay'=>'icons.unionpay','maestro'=>'icons.maestro'];
                            $cardIcon = $iconMap[$brand] ?? null;
                        @endphp
                        <span class="inline-flex items-center gap-2 whitespace-nowrap">
                            @if ($cardIcon)
                                <x-dynamic-component :component="$cardIcon" class="h-6 w-auto shrink-0" />
                            @elseif ($brand)
                                <x-heroicon-o-credit-card class="size-4 shrink-0 text-gray-400" />
                            @endif
                            <span class="text-gray-900 dark:text-gray-100">
                                {{ collect([
                                    $this->record->payment_method_brand ? str($this->record->payment_method_brand)->headline()->toString() : null,
                                    $this->record->payment_method_last4 ? '•••• '.$this->record->payment_method_last4 : null
                                ])->filter()->join(' ') ?: '—' }}
                            </span>
                        </span>
                    </x-ui.detail-row>
                </div>
            </x-filament::section>
        </section>

        @if ($this->record->subscription)
            @php $sub = $this->record->subscription; @endphp
            {{-- Recurring Plan --}}
            <section id="recurring-plan" class="scroll-mt-24">
                <x-filament::section icon="heroicon-o-arrow-path">
                    <x-slot name="heading">
                        <div class="flex items-center gap-2">
                            Recurring Plan
                            <a href="{{ route('filament.app.resources.subscriptions.view', $sub->public_id) }}"
                               class="ml-auto text-sm font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 transition-colors">
                                View Plan &rarr;
                            </a>
                        </div>
                    </x-slot>
                    <div class="space-y-1.5">
                        <x-ui.detail-row label="Plan ID" label-width="180px">
                            <span class="font-mono">{{ $sub->public_id }}</span>
                        </x-ui.detail-row>
                        <x-ui.detail-row label="Status" label-width="180px">
                            <x-ui.status-badge status="{{ $sub->status->value }}">
                                {{ str($sub->status->value)->headline() }}
                            </x-ui.status-badge>
                        </x-ui.detail-row>
                        <x-ui.detail-row label="Amount" label-width="180px"
                            :value="strtoupper($sub->currency ?? 'MYR').' '.number_format((float) $sub->amount, 2)" />
                        <x-ui.detail-row label="Frequency" label-width="180px"
                            :value="str($sub->interval->value)->headline()->toString()" />
                        <x-ui.detail-row label="Next Billing Date" label-width="180px"
                            :value="$sub->current_period_end?->format('d M Y, h:i A') ?? '—'" />
                        @if ($sub->paused_until)
                            <x-ui.detail-row label="Paused Until" label-width="180px">
                                <span class="text-amber-700 dark:text-amber-400">{{ $sub->paused_until->format('d M Y') }}</span>
                            </x-ui.detail-row>
                        @endif
                        @if ($sub->cancel_at)
                            <x-ui.detail-row label="Ends On" label-width="180px">
                                <span class="text-red-600 dark:text-red-400">{{ $sub->cancel_at->format('d M Y') }}</span>
                            </x-ui.detail-row>
                        @endif
                        <x-ui.detail-row label="Started" label-width="180px" :value="$sub->created_at->format('d M Y')" />
                    </div>
                </x-filament::section>
            </section>
        @endif

        {{-- Personal Information --}}
        <section id="personal-info" class="scroll-mt-24">
            <x-filament::section icon="heroicon-o-user">
                <x-slot name="heading">Personal Information</x-slot>
                <div class="space-y-1.5">
                    <x-ui.detail-row label="Name" label-width="120px">
                        <span class="flex items-center gap-2">
                            {{ $this->record->donor?->name ?? '—' }}
                            @if ($this->record->is_anonymous)
                                <span class="text-xs text-gray-400 dark:text-gray-500 italic">(Anonymous)</span>
                            @endif
                        </span>
                    </x-ui.detail-row>

                    <x-ui.detail-row label="Email" label-width="120px">
                        <span class="flex items-center gap-1.5">
                            {{ $this->record->donor?->email ?? '—' }}
                            @if ($this->record->donor?->email)
                                <x-ui.copy-button :value="$this->record->donor->email" size="sm" />
                            @endif
                        </span>
                    </x-ui.detail-row>

                    <x-ui.detail-row label="Phone" label-width="120px" :value="$this->record->donor?->phone ?? '—'" />

                    @php
                        $donorCountryCode = $this->record->donor?->country ?? $this->record->donor_country;
                        $donorCountryCode = $donorCountryCode ? strtoupper($donorCountryCode) : null;
                        $donorCountryName = $donorCountryCode ? (\Locale::getDisplayRegion('-'.$donorCountryCode, 'en') ?: $donorCountryCode) : '—';
                    @endphp
                    <x-ui.detail-row label="Country" label-width="120px" :value="$donorCountryName" />

                    @if ($this->record->donor?->title || $this->record->donor?->occupation)
                        <x-ui.detail-row label="Title" label-width="120px" :value="$this->record->donor->title ?? '—'" />
                        <x-ui.detail-row label="Occupation" label-width="120px" :value="$this->record->donor->occupation ?? '—'" />
                    @endif
                </div>
            </x-filament::section>
        </section>

        {{-- Sources --}}
        <section id="sources" class="scroll-mt-24">
            @php
                $utmParams = $this->record->utm_params;
                if (is_string($utmParams)) { $utmParams = json_decode($utmParams, true); }
                $utmParams = is_array($utmParams) ? $utmParams : [];
            @endphp
            <x-filament::section icon="heroicon-o-globe-alt">
                <x-slot name="heading">Sources</x-slot>
                <div class="space-y-1.5">
                    <x-ui.detail-row label="Page URL" label-width="160px">
                        <span class="truncate">{{ $this->record->page_url ?? '—' }}</span>
                        @if ($this->record->page_url)
                            <x-ui.copy-button :value="$this->record->page_url" size="sm" />
                        @endif
                    </x-ui.detail-row>
                    <x-ui.detail-row label="Element" label-width="160px" :value="$this->record->element_label ?? '—'" />
                    <x-ui.detail-row label="UTM Source" label-width="160px" :value="$utmParams['source'] ?? '—'" />
                    <x-ui.detail-row label="UTM Medium" label-width="160px" :value="$utmParams['medium'] ?? '—'" />
                    <x-ui.detail-row label="UTM Campaign" label-width="160px" :value="$utmParams['campaign'] ?? '—'" />
                    @if ($this->record->donor_message)
                        <x-ui.detail-row label="Donor Message" label-width="160px">
                            <span class="whitespace-pre-wrap">{{ $this->record->donor_message }}</span>
                        </x-ui.detail-row>
                    @endif
                </div>
            </x-filament::section>
        </section>

        {{-- Insights --}}
        <section id="insights" class="scroll-mt-24">
            <x-filament::section icon="heroicon-o-chart-pie">
                <x-slot name="heading">Insights</x-slot>
                <div class="space-y-1.5">
                    <x-ui.detail-row label="IP Address" label-width="120px">
                        <span class="flex items-center gap-1.5">
                            {{ $this->record->ip_address ?? '—' }}
                            @if ($this->record->ip_address)
                                <x-ui.copy-button :value="$this->record->ip_address" size="sm" />
                            @endif
                        </span>
                    </x-ui.detail-row>

                    @php
                        $deviceIcon2 = match ($this->record->device_type) {
                            'mobile' => 'heroicon-o-device-phone-mobile',
                            'tablet' => 'heroicon-o-device-tablet',
                            'desktop' => 'heroicon-o-computer-desktop',
                            default => null,
                        };
                        $deviceLabel2 = match ($this->record->device_type) {
                            'mobile' => 'Mobile',
                            'tablet' => 'Tablet',
                            'desktop' => 'Desktop',
                            default => null,
                        };
                    @endphp
                    <x-ui.detail-row label="Device" label-width="120px" :icon="$deviceIcon2">
                        {{ $deviceLabel2 ?? '—' }}
                    </x-ui.detail-row>

                    <x-ui.detail-row label="Browser" label-width="120px" :value="$this->record->browser ?? '—'" />
                    <x-ui.detail-row label="OS" label-width="120px" :value="$this->record->os ?? '—'" />
                    <x-ui.detail-row label="Location" label-width="120px">
                        @if ($this->record->geo_city && $this->record->geo_region)
                            {{ $this->record->geo_city }}, {{ $this->record->geo_region }}
                        @elseif ($this->record->geo_city)
                            {{ $this->record->geo_city }}
                        @else
                            —
                        @endif
                    </x-ui.detail-row>
                </div>
            </x-filament::section>
        </section>

        {{-- Receipts --}}
        <section id="receipts" class="scroll-mt-24">
            <x-filament::section icon="heroicon-o-document-text">
                <x-slot name="heading">Receipts</x-slot>
                <div class="space-y-1.5">
                    <x-ui.detail-row label="Receipt No." label-width="160px" :value="$this->record->invoice_number ?? '—'" />
                    <x-ui.detail-row label="Receipt Sent" label-width="160px"
                        :value="$this->record->receipt_sent_at?->format('d M Y, h:i A') ?? '—'" />
                    @if ($this->record->status === \App\Enums\DonationStatus::Succeeded)
                        <x-ui.detail-row label="Download" label-width="160px">
                            <a href="{{ route('donations.receipt.download', $this->record) }}" target="_blank"
                               class="inline-flex items-center gap-1.5 text-sm font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 transition-colors">
                                <x-heroicon-o-arrow-down-tray class="size-4" />
                                Download Receipt
                            </a>
                        </x-ui.detail-row>
                    @endif
                </div>
            </x-filament::section>
        </section>
    </div>

    {{-- Right Sticky Nav --}}
    <div class="w-56 shrink-0 hidden md:block">
        <div class="sticky top-24 space-y-3">
            @if ($this->record->status === \App\Enums\DonationStatus::Succeeded)
                <div class="space-y-2 px-3 py-2">
                    <a href="#"
                       wire:click="refundDonation"
                       wire:confirm="Refund {{ strtoupper($this->record->currency) }} {{ number_format((float) $this->record->gross_amount, 2) }} to {{ $this->record->donor?->name }}? This cannot be undone."
                       class="flex items-center gap-2 text-sm font-medium text-red-600 transition-colors hover:text-red-700 dark:text-red-400 dark:hover:text-red-300">
                        <x-heroicon-o-arrow-uturn-left class="size-4" />
                        Refund
                    </a>

                    <a href="{{ route('donations.receipt.download', $this->record) }}"
                       target="_blank"
                       class="flex items-center gap-2 text-sm font-medium text-gray-600 transition-colors hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200">
                        <x-heroicon-o-arrow-down-tray class="size-4" />
                        Download Receipt
                    </a>
                </div>

                <div class="border-t border-gray-200 dark:border-gray-700"></div>
            @endif

            @php
                $navItems = [
                    ['id' => 'general', 'label' => 'Donation', 'icon' => 'heroicon-o-information-circle'],
                    ['id' => 'payment-fee', 'label' => 'Payment & Fees', 'icon' => 'heroicon-o-banknotes'],
                ];
                if ($this->record->subscription) {
                    $navItems[] = ['id' => 'recurring-plan', 'label' => 'Recurring Plan', 'icon' => 'heroicon-o-arrow-path'];
                }
                $navItems = array_merge($navItems, [
                    ['id' => 'personal-info', 'label' => 'Personal Information', 'icon' => 'heroicon-o-user'],
                    ['id' => 'sources', 'label' => 'Sources', 'icon' => 'heroicon-o-globe-alt'],
                    ['id' => 'insights', 'label' => 'Insights', 'icon' => 'heroicon-o-chart-pie'],
                    ['id' => 'receipts', 'label' => 'Receipts', 'icon' => 'heroicon-o-document-text'],
                ]);
            @endphp
            <div class="space-y-1">
                @foreach ($navItems as $item)
                    <button
                        type="button"
                        @click.prevent="scrollTo('{{ $item['id'] }}')"
                        class="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors"
                        :class="activeSection === '{{ $item['id'] }}'
                            ? 'bg-primary-600 text-white shadow-sm dark:bg-primary-500'
                            : 'text-gray-600 hover:bg-gray-50 dark:text-gray-400 dark:hover:bg-gray-800/50'"
                    >
                        <x-dynamic-component :component="$item['icon']" class="size-4 shrink-0" />
                        {{ $item['label'] }}
                    </button>
                @endforeach
            </div>
        </div>
    </div>
</div>
</x-filament-panels::page>
