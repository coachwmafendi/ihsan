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
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center gap-2">
                    <x-heroicon-o-information-circle class="size-5 text-gray-400 dark:text-gray-500" />
                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Donation</h3>
                    <button
                        type="button"
                        wire:click="mountAction('editDonation')"
                        class="ml-auto text-sm font-medium text-primary-600 transition hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300"
                    >
                        Edit
                    </button>
                </div>
                <div class="px-6 py-4 space-y-2 text-sm">
                    <div class="flex items-baseline gap-8 py-1">
                        <span class="w-[120px] shrink-0 text-gray-500 dark:text-gray-400">Donation Amount</span>
                        <span class="text-gray-900 dark:text-gray-100">
                            @if ($this->record->currency !== 'myr' && $this->record->base_amount)
                                {{ strtoupper($this->record->currency) }} {{ number_format((float) $this->record->gross_amount, 2) }}
                                <span class="text-gray-400 dark:text-gray-500 ml-1.5">≈ MYR {{ number_format((float) $this->record->base_amount, 2) }}</span>
                            @else
                                {{ strtoupper($this->record->currency) }} {{ number_format((float) $this->record->gross_amount, 2) }}
                            @endif
                        </span>
                    </div>

                    <div class="flex items-center gap-8 py-1">
                        <span class="w-[120px] shrink-0 text-gray-500 dark:text-gray-400">Donation ID</span>
                        <div class="flex items-center gap-1.5">
                            <span class="text-gray-900 dark:text-gray-100">{{ $this->record->public_id }}</span>
                            <button
                                type="button"
                                @click="navigator.clipboard.writeText('{{ $this->record->public_id }}'); $dispatch('notify', { message: 'Copied' })"
                                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                                title="Copy"
                            >
                                <x-heroicon-o-clipboard-document class="size-3.5" />
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center gap-8 py-1">
                        <span class="w-[120px] shrink-0 text-gray-500 dark:text-gray-400">Status</span>
                        <span>
                            @php
                                $statusColor = match (ucfirst($this->record->status->value)) {
                                    'Pending' => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
                                    'Succeeded' => 'bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                                    'Failed' => 'bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                                    'Refunded' => 'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                                    default => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
                                };
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium {{ $statusColor }}">
                                {{ ucfirst($this->record->status->value) }}
                            </span>
                        </span>
                    </div>

                    <div class="flex items-center gap-8 py-1">
                        <span class="w-[120px] shrink-0 text-gray-500 dark:text-gray-400">Supporter</span>
                        @if ($this->record->donor)
                            <a
                                href="{{ route('filament.app.resources.supporters.view', $this->record->donor->public_id) }}"
                                class="text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 transition-colors"
                            >
                                {{ $this->record->donor->name }}
                            </a>
                        @else
                            <span class="text-gray-900 dark:text-gray-100">—</span>
                        @endif
                    </div>

                    <div class="flex items-center gap-8 py-1">
                        <span class="w-[120px] shrink-0 text-gray-500 dark:text-gray-400">Campaign</span>
                        <span class="text-gray-900 dark:text-gray-100">{{ $this->record->campaign?->title ?? '—' }}</span>
                    </div>

                    <div class="flex items-baseline gap-8 py-1">
                        <span class="w-[120px] shrink-0 text-gray-500 dark:text-gray-400">Donation Date</span>
                        <span class="text-gray-900 dark:text-gray-100">{{ $this->record->created_at->format('d M Y, h:i A') }}</span>
                    </div>

                    <div class="flex items-baseline gap-8 py-1">
                        <span class="w-[120px] shrink-0 text-gray-500 dark:text-gray-400">Success Date</span>
                        <span class="text-gray-900 dark:text-gray-100">
                            @if ($this->record->status->value === 'succeeded')
                                {{ ($this->record->receipt_sent_at ?? $this->record->updated_at)?->format('d M Y, h:i A') ?? '—' }}
                            @else
                                —
                            @endif
                        </span>
                    </div>

                    <div class="flex items-center gap-8 py-1">
                        <span class="w-[120px] shrink-0 text-gray-500 dark:text-gray-400">Frequency</span>
                        <span class="text-gray-900 dark:text-gray-100">
                            @if ($this->record->subscription)
                                {{ str($this->record->subscription->interval->value)->headline() }}
                            @else
                                {{ str($this->record->type->value)->headline() }}
                            @endif
                        </span>
                    </div>
                </div>
            </div>
        </section>

        {{-- Payment & Fee (moved up) --}}
        <section id="payment-fee" class="scroll-mt-24">
            @php
                $gross = (float) $this->record->gross_amount;
                $stripeFee = (float) ($this->record->stripe_fee ?? 0);
                $platformFee = (float) ($this->record->processing_fee ?? 0);
                $totalFees = $stripeFee + $platformFee;
                $feeCovered = (float) ($this->record->donor_fee_covered ?? 0);
                $beforeFeesCovered = $gross - $feeCovered;
                $payout = (float) $this->record->net_amount;
                $effectiveFeeRate = $gross > 0 ? ($totalFees / $gross * 100) : 0;
                $curr = strtoupper($this->record->currency);
                $isForeign = $this->record->currency !== 'myr' && $this->record->base_amount;
            @endphp
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center gap-2">
                    <x-heroicon-o-banknotes class="size-5 text-gray-400 dark:text-gray-500" />
                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Payment & Fees</h3>
                </div>
                <div class="px-6 py-4 space-y-2 text-sm">
                    <div class="flex items-baseline gap-8 py-1">
                        <span class="w-[180px] shrink-0 text-gray-500 dark:text-gray-400">Payment Amount</span>
                        <span class="text-gray-900 dark:text-gray-100">
                            {{ $curr }} {{ number_format($gross, 2) }}
                            @if ($isForeign)
                                <span class="text-gray-400 dark:text-gray-500 ml-1.5">≈ MYR {{ number_format((float) $this->record->base_amount, 2) }}</span>
                            @endif
                        </span>
                    </div>
                    <div class="flex items-baseline gap-8 py-1">
                        <span class="w-[180px] shrink-0 text-gray-500 dark:text-gray-400">Before Fees Covered</span>
                        <span class="text-gray-900 dark:text-gray-100">{{ $curr }} {{ number_format($beforeFeesCovered, 2) }}</span>
                    </div>
                    <div class="flex items-center gap-8 py-1">
                        <span class="w-[180px] shrink-0 text-gray-500 dark:text-gray-400">Stripe Fee</span>
                        <span class="text-gray-900 dark:text-gray-100">{{ $curr }} {{ number_format($stripeFee, 2) }}</span>
                    </div>
                    <div class="flex items-center gap-8 py-1">
                        <span class="w-[180px] shrink-0 text-gray-500 dark:text-gray-400">Platform Fee</span>
                        <span class="text-gray-900 dark:text-gray-100">{{ $curr }} 0.00</span>
                    </div>
                    <div class="flex items-center gap-8 py-1">
                        <span class="w-[180px] shrink-0 text-gray-500 dark:text-gray-400">Processing Fee</span>
                        <span class="text-gray-900 dark:text-gray-100">{{ $curr }} {{ number_format($platformFee, 2) }}</span>
                    </div>
                    <div class="flex items-center gap-8 py-1">
                        <span class="w-[180px] shrink-0 text-gray-500 dark:text-gray-400">Payment Processing Fee</span>
                        <span class="text-gray-900 dark:text-gray-100">{{ $curr }} {{ number_format($totalFees, 2) }}</span>
                    </div>
                    <div class="flex items-center gap-8 py-1">
                        <span class="w-[180px] shrink-0 text-gray-500 dark:text-gray-400">Payout Amount</span>
                        <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $curr }} {{ number_format($payout, 2) }}</span>
                    </div>
                    <div class="flex items-center gap-8 py-1">
                        <span class="w-[180px] shrink-0 text-gray-500 dark:text-gray-400">Fee Covered</span>
                        @if ($feeCovered > 0)
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-medium bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-400">
                                <x-heroicon-o-check class="size-3" />
                                {{ $curr }} {{ number_format($feeCovered, 2) }}
                            </span>
                        @else
                            <span class="text-gray-500 dark:text-gray-400">No</span>
                        @endif
                    </div>
                    <div class="flex items-center gap-8 py-1">
                        <span class="w-[180px] shrink-0 text-gray-500 dark:text-gray-400">Effective Fee</span>
                        <span class="text-gray-900 dark:text-gray-100">{{ number_format($effectiveFeeRate, 2) }}%</span>
                    </div>
                    <div class="border-t border-gray-100 dark:border-gray-800 my-2"></div>
                    <div class="flex items-center gap-8 py-1">
                        <span class="w-[180px] shrink-0 text-gray-500 dark:text-gray-400">Payment ID</span>
                        <div class="flex items-center gap-1.5 min-w-0">
                            <span class="font-mono text-gray-900 dark:text-gray-100 truncate">{{ $this->record->stripe_payment_intent_id ?? '—' }}</span>
                            @if ($this->record->stripe_payment_intent_id)
                                <button type="button" @click="navigator.clipboard.writeText('{{ $this->record->stripe_payment_intent_id }}'); $dispatch('notify', { message: 'Copied' })" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors shrink-0" title="Copy">
                                    <x-heroicon-o-clipboard-document class="size-3.5" />
                                </button>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center gap-8 py-1">
                        <span class="w-[180px] shrink-0 text-gray-500 dark:text-gray-400">Payment Processor</span>
                        <span class="flex items-center gap-2 text-gray-900 dark:text-gray-100">
                            <x-icons.stripe class="h-5 w-auto rounded" />
                            Stripe
                        </span>
                    </div>
                    <div class="flex items-center gap-8 py-1">
                        <span class="w-[180px] shrink-0 text-gray-500 dark:text-gray-400">Payment Method</span>
                        <span class="text-gray-900 dark:text-gray-100">{{ $this->record->payment_method_type ? str($this->record->payment_method_type)->headline() : '—' }}</span>
                    </div>
                    <div class="flex items-center gap-8 py-1">
                        <span class="w-[180px] shrink-0 text-gray-500 dark:text-gray-400">Credit Card</span>
                        <span class="flex items-center gap-2 text-gray-900 dark:text-gray-100">
                            @php
                                $brand = strtolower($this->record->payment_method_brand ?? '');
                                $iconMap = ['visa'=>'icons.visa','mastercard'=>'icons.mastercard','amex'=>'icons.amex','american express'=>'icons.amex','discover'=>'icons.discover','jcb'=>'icons.jcb','diners'=>'icons.diners','diners club'=>'icons.diners','unionpay'=>'icons.unionpay','maestro'=>'icons.maestro'];
                                $cardIcon = $iconMap[$brand] ?? null;
                            @endphp
                            @if ($cardIcon)
                                <x-dynamic-component :component="$cardIcon" class="w-10 h-6" />
                            @elseif ($brand)
                                <x-heroicon-o-credit-card class="size-4 text-gray-400" />
                            @endif
                            {{ collect([$this->record->payment_method_brand ? str($this->record->payment_method_brand)->headline()->toString() : null, $this->record->payment_method_last4 ? '•••• '.$this->record->payment_method_last4 : null])->filter()->join(' ') ?: '—' }}
                        </span>
                    </div>
                </div>
            </div>
        </section>

        @if ($this->record->subscription)
            @php $sub = $this->record->subscription; @endphp
            {{-- Recurring Plan --}}
            <section id="recurring-plan" class="scroll-mt-24">
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center gap-2">
                        <x-heroicon-o-arrow-path class="size-5 text-gray-400 dark:text-gray-500" />
                        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Recurring Plan</h3>
                        <a href="{{ route('filament.app.resources.subscriptions.view', $sub->public_id) }}" class="ml-auto text-sm font-medium text-primary-600 transition hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300">View Plan</a>
                    </div>
                    <div class="px-6 py-4 space-y-2 text-sm">
                        <div class="flex items-center gap-8 py-1">
                            <span class="w-[180px] shrink-0 text-gray-500 dark:text-gray-400">Plan ID</span>
                            <span class="font-mono text-gray-900 dark:text-gray-100">{{ $sub->public_id }}</span>
                        </div>
                        <div class="flex items-center gap-8 py-1">
                            <span class="w-[180px] shrink-0 text-gray-500 dark:text-gray-400">Status</span>
                            @php $subStatusColor = match ($sub->status->value) { 'active'=>'bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-400', 'past_due'=>'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400', 'cancelled'=>'bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-400', default=>'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300' }; @endphp
                            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium {{ $subStatusColor }}">{{ str($sub->status->value)->headline() }}</span>
                        </div>
                        <div class="flex items-center gap-8 py-1">
                            <span class="w-[180px] shrink-0 text-gray-500 dark:text-gray-400">Amount</span>
                            <span class="text-gray-900 dark:text-gray-100">{{ strtoupper($sub->currency ?? 'MYR') }} {{ number_format((float) $sub->amount, 2) }}</span>
                        </div>
                        <div class="flex items-center gap-8 py-1">
                            <span class="w-[180px] shrink-0 text-gray-500 dark:text-gray-400">Frequency</span>
                            <span class="text-gray-900 dark:text-gray-100">{{ str($sub->interval->value)->headline() }}</span>
                        </div>
                        <div class="flex items-baseline gap-8 py-1">
                            <span class="w-[180px] shrink-0 text-gray-500 dark:text-gray-400">Next Billing Date</span>
                            <span class="text-gray-900 dark:text-gray-100">{{ $sub->current_period_end?->format('d M Y, h:i A') ?? '—' }}</span>
                        </div>
                        @if ($sub->paused_until)
                            <div class="flex items-baseline gap-8 py-1">
                                <span class="w-[180px] shrink-0 text-gray-500 dark:text-gray-400">Paused Until</span>
                                <span class="text-amber-700 dark:text-amber-400">{{ $sub->paused_until->format('d M Y') }}</span>
                            </div>
                        @endif
                        @if ($sub->cancel_at)
                            <div class="flex items-baseline gap-8 py-1">
                                <span class="w-[180px] shrink-0 text-gray-500 dark:text-gray-400">Ends On</span>
                                <span class="text-red-600 dark:text-red-400">{{ $sub->cancel_at->format('d M Y') }}</span>
                            </div>
                        @endif
                        <div class="flex items-baseline gap-8 py-1">
                            <span class="w-[180px] shrink-0 text-gray-500 dark:text-gray-400">Started</span>
                            <span class="text-gray-900 dark:text-gray-100">{{ $sub->created_at->format('d M Y') }}</span>
                        </div>
                    </div>
                </div>
            </section>
        @endif

        {{-- Personal Information --}}
        <section id="personal-info" class="scroll-mt-24">
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center gap-2">
                    <x-heroicon-o-user class="size-5 text-gray-400 dark:text-gray-500" />
                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Personal Information</h3>
                </div>
                <div class="px-6 py-4 space-y-2 text-sm">
                    <div class="flex items-center gap-8 py-1">
                        <span class="w-[120px] shrink-0 text-gray-500 dark:text-gray-400">Name</span>
                        <span class="flex items-center gap-2 text-gray-900 dark:text-gray-100">
                            {{ $this->record->donor?->name ?? '—' }}
                            @if ($this->record->is_anonymous)
                                <span class="text-xs text-gray-400 dark:text-gray-500 italic">Anonymous donation</span>
                            @endif
                        </span>
                    </div>

                    <div class="flex items-center gap-8 py-1">
                        <span class="w-[120px] shrink-0 text-gray-500 dark:text-gray-400">Email</span>
                        <div class="flex items-center gap-1.5">
                            <span class="text-gray-900 dark:text-gray-100">{{ $this->record->donor?->email ?? '—' }}</span>
                            @if ($this->record->donor?->email)
                                <button
                                    type="button"
                                    @click="navigator.clipboard.writeText('{{ $this->record->donor->email }}'); $dispatch('notify', { message: 'Copied' })"
                                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                                    title="Copy"
                                >
                                    <x-heroicon-o-clipboard-document class="size-3.5" />
                                </button>
                            @endif
                        </div>
                    </div>

                    <div class="flex items-center gap-8 py-1">
                        <span class="w-[120px] shrink-0 text-gray-500 dark:text-gray-400">Phone</span>
                        <span class="text-gray-900 dark:text-gray-100">{{ $this->record->donor?->phone ?? '—' }}</span>
                    </div>

                    @php
                        $donorCountryCode = $this->record->donor?->country ?? $this->record->donor_country;
                        $donorCountryCode = $donorCountryCode ? strtoupper($donorCountryCode) : null;
                        $donorCountryName = $donorCountryCode ? (\Locale::getDisplayRegion('-'.$donorCountryCode, 'en') ?: $donorCountryCode) : '—';
                    @endphp
                    <div class="flex items-center gap-8 py-1">
                        <span class="w-[120px] shrink-0 text-gray-500 dark:text-gray-400">Country</span>
                        <span class="text-gray-900 dark:text-gray-100">{{ $donorCountryName }}</span>
                    </div>

                    @if ($this->record->donor?->title || $this->record->donor?->occupation)
                        <div class="flex items-center gap-8 py-1">
                            <span class="w-[120px] shrink-0 text-gray-500 dark:text-gray-400">Title</span>
                            <span class="text-gray-900 dark:text-gray-100">{{ $this->record->donor->title ?? '—' }}</span>
                        </div>

                        <div class="flex items-center gap-8 py-1">
                            <span class="w-[120px] shrink-0 text-gray-500 dark:text-gray-400">Occupation</span>
                            <span class="text-gray-900 dark:text-gray-100">{{ $this->record->donor->occupation ?? '—' }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </section>

        {{-- Donor & Campaign --}}
        <section id="donor-campaign" class="scroll-mt-24">
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center gap-2">
                    <x-heroicon-o-user-group class="size-5 text-gray-400 dark:text-gray-500" />
                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Donor & Campaign</h3>
                </div>
                <div class="px-6 py-4 space-y-2 text-sm">
                    <div class="flex items-center gap-8 py-1">
                        <span class="w-[120px] shrink-0 text-gray-500 dark:text-gray-400">Supporter</span>
                        <div class="flex items-center gap-1.5">
                            <span class="text-gray-900 dark:text-gray-100">{{ $this->record->donor?->name ?? '—' }}</span>
                            @php
                                $deviceIcon = match ($this->record->device_type) {
                                    'mobile' => 'heroicon-o-device-phone-mobile',
                                    'tablet' => 'heroicon-o-device-tablet',
                                    'desktop' => 'heroicon-o-computer-desktop',
                                    default => null,
                                };
                                $deviceLabel = match ($this->record->device_type) {
                                    'mobile' => 'Mobile',
                                    'tablet' => 'Tablet',
                                    'desktop' => 'Desktop',
                                    default => null,
                                };
                            @endphp
                            @if ($deviceIcon)
                                <span title="{{ $deviceLabel }}">
                                    <x-dynamic-component :component="$deviceIcon" class="size-4 text-gray-400 dark:text-gray-500" />
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="flex items-center gap-8 py-1">
                        <span class="w-[120px] shrink-0 text-gray-500 dark:text-gray-400">Campaign</span>
                        <span class="text-gray-900 dark:text-gray-100">{{ $this->record->campaign?->title ?? '—' }}</span>
                    </div>

                    <div class="flex items-center gap-8 py-1">
                        <span class="w-[120px] shrink-0 text-gray-500 dark:text-gray-400">Email</span>
                        <div class="flex items-center gap-1.5">
                            <span class="text-gray-900 dark:text-gray-100">{{ $this->record->donor?->email ?? '—' }}</span>
                            @if ($this->record->donor?->email)
                                <button
                                    type="button"
                                    @click="navigator.clipboard.writeText('{{ $this->record->donor->email }}'); $dispatch('notify', { message: 'Copied' })"
                                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                                    title="Copy"
                                >
                                    <x-heroicon-o-clipboard-document class="size-3.5" />
                                </button>
                            @endif
                        </div>
                    </div>

                    <div class="flex items-center gap-8 py-1">
                        <span class="w-[120px] shrink-0 text-gray-500 dark:text-gray-400">Phone</span>
                        <span class="text-gray-900 dark:text-gray-100">{{ $this->record->donor?->phone ?? '—' }}</span>
                    </div>

                    <div class="flex items-center gap-8 py-1">
                        <span class="w-[120px] shrink-0 text-gray-500 dark:text-gray-400">Payment Method</span>
                        <span class="flex items-center gap-2 text-gray-900 dark:text-gray-100">
                            @php
                                $brand = strtolower($this->record->payment_method_brand ?? '');
                                $iconMap = [
                                    'visa' => 'icons.visa',
                                    'mastercard' => 'icons.mastercard',
                                    'amex' => 'icons.amex',
                                    'american express' => 'icons.amex',
                                    'discover' => 'icons.discover',
                                    'jcb' => 'icons.jcb',
                                    'diners' => 'icons.diners',
                                    'diners club' => 'icons.diners',
                                    'unionpay' => 'icons.unionpay',
                                    'maestro' => 'icons.maestro',
                                ];
                                $iconComponent = $iconMap[$brand] ?? null;
                            @endphp
                            @if ($iconComponent)
                                <x-dynamic-component :component="$iconComponent" class="w-10 h-6" />
                            @else
                                <x-heroicon-o-credit-card class="w-5 h-5 text-gray-400" />
                            @endif
                            <span>
                                {{ collect([
                                    $this->record->payment_method_brand ? str($this->record->payment_method_brand)->headline()->toString() : null,
                                    $this->record->payment_method_last4 ? '•••• '.$this->record->payment_method_last4 : null,
                                ])->filter()->join(' ') ?: '—' }}
                            </span>
                        </span>
                    </div>

                    <div class="flex items-center gap-8 py-1">
                        <span class="w-[120px] shrink-0 text-gray-500 dark:text-gray-400">Anonymous</span>
                        <span class="text-gray-900 dark:text-gray-100">{{ $this->record->is_anonymous ? 'Yes' : 'No' }}</span>
                    </div>

                    <div class="flex items-center gap-8 py-1">
                        <span class="w-[120px] shrink-0 text-gray-500 dark:text-gray-400">Element</span>
                        <span class="text-gray-900 dark:text-gray-100">{{ $this->record->element_label ?? '—' }}</span>
                    </div>

                    <div class="flex items-center gap-8 py-1">
                        <span class="w-[120px] shrink-0 text-gray-500 dark:text-gray-400">URL</span>
                        <div class="flex items-center gap-1.5 min-w-0">
                            <span class="text-gray-900 dark:text-gray-100 truncate">{{ $this->record->page_url ?? '—' }}</span>
                            @if ($this->record->page_url)
                                <button
                                    type="button"
                                    @click="navigator.clipboard.writeText('{{ $this->record->page_url }}'); $dispatch('notify', { message: 'Copied' })"
                                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors shrink-0"
                                    title="Copy"
                                >
                                    <x-heroicon-o-clipboard-document class="size-3.5" />
                                </button>
                            @endif
                        </div>
                    </div>

                    @if ($this->record->donor_message)
                        <div class="flex items-start gap-8 py-1">
                            <span class="w-[120px] shrink-0 text-gray-500 dark:text-gray-400 pt-0.5">Message</span>
                            <span class="text-gray-900 dark:text-gray-100 whitespace-pre-wrap">{{ $this->record->donor_message }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </section>

        {{-- Payment & Fee --}}
        <section id="payment-fee" class="scroll-mt-24">
            @php
                $gross = (float) $this->record->gross_amount;
                $stripeFee = (float) ($this->record->stripe_fee ?? 0);
                $platformFee = (float) ($this->record->processing_fee ?? 0);
                $totalFees = $stripeFee + $platformFee;
                $feeCovered = (float) ($this->record->donor_fee_covered ?? 0);
                $beforeFeesCovered = $gross - $feeCovered;
                $payout = (float) $this->record->net_amount;
                $effectiveFeeRate = $gross > 0 ? ($totalFees / $gross * 100) : 0;
                $curr = strtoupper($this->record->currency);
                $isForeign = $this->record->currency !== 'myr' && $this->record->base_amount;
            @endphp
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center gap-2">
                    <x-heroicon-o-banknotes class="size-5 text-gray-400 dark:text-gray-500" />
                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Payment & Fee</h3>
                </div>
                <div class="px-6 py-4 space-y-2 text-sm">
                    <div class="flex items-baseline gap-8 py-1">
                        <span class="w-[180px] shrink-0 text-gray-500 dark:text-gray-400">Payment Amount</span>
                        <span class="text-gray-900 dark:text-gray-100">
                            {{ $curr }} {{ number_format($gross, 2) }}
                            @if ($isForeign)
                                <span class="text-gray-400 dark:text-gray-500 ml-1.5">≈ MYR {{ number_format((float) $this->record->base_amount, 2) }}</span>
                            @endif
                        </span>
                    </div>

                    <div class="flex items-baseline gap-8 py-1">
                        <span class="w-[180px] shrink-0 text-gray-500 dark:text-gray-400">Before Fees Covered</span>
                        <span class="text-gray-900 dark:text-gray-100">{{ $curr }} {{ number_format($beforeFeesCovered, 2) }}</span>
                    </div>

                    <div class="flex items-center gap-8 py-1">
                        <span class="w-[180px] shrink-0 text-gray-500 dark:text-gray-400">Stripe Fee</span>
                        <span class="text-gray-900 dark:text-gray-100">{{ $curr }} {{ number_format($stripeFee, 2) }}</span>
                    </div>

                    <div class="flex items-center gap-8 py-1">
                        <span class="w-[180px] shrink-0 text-gray-500 dark:text-gray-400">Platform Fee</span>
                        <span class="text-gray-900 dark:text-gray-100">{{ $curr }} 0.00</span>
                    </div>

                    <div class="flex items-center gap-8 py-1">
                        <span class="w-[180px] shrink-0 text-gray-500 dark:text-gray-400">Processing Fee</span>
                        <span class="text-gray-900 dark:text-gray-100">{{ $curr }} {{ number_format($platformFee, 2) }}</span>
                    </div>

                    <div class="flex items-center gap-8 py-1">
                        <span class="w-[180px] shrink-0 text-gray-500 dark:text-gray-400">Payment Processing Fee</span>
                        <span class="text-gray-900 dark:text-gray-100">{{ $curr }} {{ number_format($totalFees, 2) }}</span>
                    </div>

                    <div class="flex items-center gap-8 py-1">
                        <span class="w-[180px] shrink-0 text-gray-500 dark:text-gray-400">Payout Amount</span>
                        <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $curr }} {{ number_format($payout, 2) }}</span>
                    </div>

                    <div class="flex items-center gap-8 py-1">
                        <span class="w-[180px] shrink-0 text-gray-500 dark:text-gray-400">Fee Covered</span>
                        @if ($feeCovered > 0)
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-medium bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-400">
                                <x-heroicon-o-check class="size-3" />
                                {{ $curr }} {{ number_format($feeCovered, 2) }}
                            </span>
                        @else
                            <span class="text-gray-500 dark:text-gray-400">No</span>
                        @endif
                    </div>

                    <div class="flex items-center gap-8 py-1">
                        <span class="w-[180px] shrink-0 text-gray-500 dark:text-gray-400">Effective Fee</span>
                        <span class="text-gray-900 dark:text-gray-100">{{ number_format($effectiveFeeRate, 2) }}%</span>
                    </div>

                    <div class="border-t border-gray-100 dark:border-gray-800 my-2"></div>

                    <div class="flex items-center gap-8 py-1">
                        <span class="w-[180px] shrink-0 text-gray-500 dark:text-gray-400">Payment ID</span>
                        <div class="flex items-center gap-1.5 min-w-0">
                            <span class="font-mono text-gray-900 dark:text-gray-100 truncate">{{ $this->record->stripe_payment_intent_id ?? '—' }}</span>
                            @if ($this->record->stripe_payment_intent_id)
                                <button
                                    type="button"
                                    @click="navigator.clipboard.writeText('{{ $this->record->stripe_payment_intent_id }}'); $dispatch('notify', { message: 'Copied' })"
                                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors shrink-0"
                                    title="Copy"
                                >
                                    <x-heroicon-o-clipboard-document class="size-3.5" />
                                </button>
                            @endif
                        </div>
                    </div>

                    <div class="flex items-center gap-8 py-1">
                        <span class="w-[180px] shrink-0 text-gray-500 dark:text-gray-400">Payment Processor</span>
                        <span class="flex items-center gap-2 text-gray-900 dark:text-gray-100">
                            <x-icons.stripe class="h-5 w-auto rounded" />
                            Stripe
                        </span>
                    </div>

                    <div class="flex items-center gap-8 py-1">
                        <span class="w-[180px] shrink-0 text-gray-500 dark:text-gray-400">Payment Method</span>
                        <span class="text-gray-900 dark:text-gray-100">{{ $this->record->payment_method_type ? str($this->record->payment_method_type)->headline() : '—' }}</span>
                    </div>

                    <div class="flex items-center gap-8 py-1">
                        <span class="w-[180px] shrink-0 text-gray-500 dark:text-gray-400">Credit Card</span>
                        <span class="flex items-center gap-2 text-gray-900 dark:text-gray-100">
                            @php
                                $brand = strtolower($this->record->payment_method_brand ?? '');
                                $iconMap = [
                                    'visa' => 'icons.visa',
                                    'mastercard' => 'icons.mastercard',
                                    'amex' => 'icons.amex',
                                    'american express' => 'icons.amex',
                                    'discover' => 'icons.discover',
                                    'jcb' => 'icons.jcb',
                                    'diners' => 'icons.diners',
                                    'diners club' => 'icons.diners',
                                    'unionpay' => 'icons.unionpay',
                                    'maestro' => 'icons.maestro',
                                ];
                                $cardIcon = $iconMap[$brand] ?? null;
                            @endphp
                            @if ($cardIcon)
                                <x-dynamic-component :component="$cardIcon" class="w-10 h-6" />
                            @elseif ($brand)
                                <x-heroicon-o-credit-card class="size-4 text-gray-400" />
                            @endif
                            {{ collect([
                                $this->record->payment_method_brand ? str($this->record->payment_method_brand)->headline()->toString() : null,
                                $this->record->payment_method_last4 ? '•••• '.$this->record->payment_method_last4 : null,
                            ])->filter()->join(' ') ?: '—' }}
                        </span>
                    </div>
                </div>
            </div>
        </section>

        {{-- Insights --}}
        <section id="insights" class="scroll-mt-24">
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center gap-2">
                    <x-heroicon-o-chart-pie class="size-5 text-gray-400 dark:text-gray-500" />
                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Insights</h3>
                </div>
                <div class="px-6 py-4 space-y-2 text-sm">
                    <div class="flex items-center gap-8 py-1">
                        <span class="w-[120px] shrink-0 text-gray-500 dark:text-gray-400">IP Address</span>
                        <div class="flex items-center gap-1.5">
                            <span class="text-gray-900 dark:text-gray-100">{{ $this->record->ip_address ?? '—' }}</span>
                            @if ($this->record->ip_address)
                                <button
                                    type="button"
                                    @click="navigator.clipboard.writeText('{{ $this->record->ip_address }}'); $dispatch('notify', { message: 'Copied' })"
                                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                                    title="Copy"
                                >
                                    <x-heroicon-o-clipboard-document class="size-3.5" />
                                </button>
                            @endif
                        </div>
                    </div>

                    <div class="flex items-center gap-8 py-1">
                        <span class="w-[120px] shrink-0 text-gray-500 dark:text-gray-400">Device</span>
                        <div class="flex items-center gap-1.5">
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
                            @if ($deviceIcon2)
                                <x-dynamic-component :component="$deviceIcon2" class="size-4 text-gray-400 dark:text-gray-500" />
                            @endif
                            <span class="text-gray-900 dark:text-gray-100">{{ $deviceLabel2 ?? '—' }}</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-8 py-1">
                        <span class="w-[120px] shrink-0 text-gray-500 dark:text-gray-400">Browser</span>
                        <span class="text-gray-900 dark:text-gray-100">{{ $this->record->browser ?? '—' }}</span>
                    </div>

                    <div class="flex items-center gap-8 py-1">
                        <span class="w-[120px] shrink-0 text-gray-500 dark:text-gray-400">OS</span>
                        <span class="text-gray-900 dark:text-gray-100">{{ $this->record->os ?? '—' }}</span>
                    </div>

                    <div class="flex items-center gap-8 py-1">
                        <span class="w-[120px] shrink-0 text-gray-500 dark:text-gray-400">Location</span>
                        <span class="text-gray-900 dark:text-gray-100">
                            @if ($this->record->geo_city && $this->record->geo_region)
                                {{ $this->record->geo_city }}, {{ $this->record->geo_region }}
                            @elseif ($this->record->geo_city)
                                {{ $this->record->geo_city }}
                            @else
                                —
                            @endif
                        </span>
                    </div>
                </div>
            </div>
        </section>

        {{-- Stripe Info --}}
        <section id="stripe-info" class="scroll-mt-24">
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center gap-2">
                    <x-heroicon-o-credit-card class="size-5 text-gray-400 dark:text-gray-500" />
                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Stripe Info</h3>
                </div>
                <div class="px-6 py-4 space-y-2 text-sm">
                    <div class="flex items-center gap-8 py-1">
                        <span class="w-[120px] shrink-0 text-gray-500 dark:text-gray-400">Payment Intent ID</span>
                        <div class="flex items-center gap-1.5 min-w-0">
                            <span class="text-gray-900 dark:text-gray-100 font-mono truncate">{{ $this->record->stripe_payment_intent_id ?? '—' }}</span>
                            @if ($this->record->stripe_payment_intent_id)
                                <button
                                    type="button"
                                    @click="navigator.clipboard.writeText('{{ $this->record->stripe_payment_intent_id }}'); $dispatch('notify', { message: 'Copied' })"
                                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors shrink-0"
                                    title="Copy"
                                >
                                    <x-heroicon-o-clipboard-document class="size-3.5" />
                                </button>
                            @endif
                        </div>
                    </div>

                    <div class="flex items-center gap-8 py-1">
                        <span class="w-[120px] shrink-0 text-gray-500 dark:text-gray-400">Charge ID</span>
                        <div class="flex items-center gap-1.5 min-w-0">
                            <span class="text-gray-900 dark:text-gray-100 font-mono truncate">{{ $this->record->stripe_charge_id ?? '—' }}</span>
                            @if ($this->record->stripe_charge_id)
                                <button
                                    type="button"
                                    @click="navigator.clipboard.writeText('{{ $this->record->stripe_charge_id }}'); $dispatch('notify', { message: 'Copied' })"
                                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors shrink-0"
                                    title="Copy"
                                >
                                    <x-heroicon-o-clipboard-document class="size-3.5" />
                                </button>
                            @endif
                        </div>
                    </div>

                    @if ($this->record->subscription_id)
                        <div class="flex items-center gap-8 py-1">
                            <span class="w-[120px] shrink-0 text-gray-500 dark:text-gray-400">Subscription</span>
                            <div class="flex items-center gap-1.5 min-w-0">
                                <span class="text-gray-900 dark:text-gray-100 font-mono truncate">{{ $this->record->subscription?->public_id ?? $this->record->subscription_id }}</span>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </section>

        @if ($this->record->subscription)
            @php $sub = $this->record->subscription; @endphp
            {{-- Recurring Plan --}}
            <section id="recurring-plan" class="scroll-mt-24">
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center gap-2">
                        <x-heroicon-o-arrow-path class="size-5 text-gray-400 dark:text-gray-500" />
                        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Recurring Plan</h3>
                        <a
                            href="{{ route('filament.app.resources.subscriptions.view', $sub->public_id) }}"
                            class="ml-auto text-sm font-medium text-primary-600 transition hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300"
                        >
                            View Plan
                        </a>
                    </div>
                    <div class="px-6 py-4 space-y-2 text-sm">
                        <div class="flex items-center gap-8 py-1">
                            <span class="w-[180px] shrink-0 text-gray-500 dark:text-gray-400">Plan ID</span>
                            <span class="font-mono text-gray-900 dark:text-gray-100">{{ $sub->public_id }}</span>
                        </div>

                        <div class="flex items-center gap-8 py-1">
                            <span class="w-[180px] shrink-0 text-gray-500 dark:text-gray-400">Status</span>
                            @php
                                $subStatusColor = match ($sub->status->value) {
                                    'active' => 'bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                                    'past_due' => 'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                                    'cancelled' => 'bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                                    default => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
                                };
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium {{ $subStatusColor }}">
                                {{ str($sub->status->value)->headline() }}
                            </span>
                        </div>

                        <div class="flex items-center gap-8 py-1">
                            <span class="w-[180px] shrink-0 text-gray-500 dark:text-gray-400">Amount</span>
                            <span class="text-gray-900 dark:text-gray-100">{{ strtoupper($sub->currency ?? 'MYR') }} {{ number_format((float) $sub->amount, 2) }}</span>
                        </div>

                        <div class="flex items-center gap-8 py-1">
                            <span class="w-[180px] shrink-0 text-gray-500 dark:text-gray-400">Frequency</span>
                            <span class="text-gray-900 dark:text-gray-100">{{ str($sub->interval->value)->headline() }}</span>
                        </div>

                        <div class="flex items-baseline gap-8 py-1">
                            <span class="w-[180px] shrink-0 text-gray-500 dark:text-gray-400">Next Billing Date</span>
                            <span class="text-gray-900 dark:text-gray-100">{{ $sub->current_period_end?->format('d M Y, h:i A') ?? '—' }}</span>
                        </div>

                        @if ($sub->paused_until)
                            <div class="flex items-baseline gap-8 py-1">
                                <span class="w-[180px] shrink-0 text-gray-500 dark:text-gray-400">Paused Until</span>
                                <span class="text-amber-700 dark:text-amber-400">{{ $sub->paused_until->format('d M Y') }}</span>
                            </div>
                        @endif

                        @if ($sub->cancel_at)
                            <div class="flex items-baseline gap-8 py-1">
                                <span class="w-[180px] shrink-0 text-gray-500 dark:text-gray-400">Ends On</span>
                                <span class="text-red-600 dark:text-red-400">{{ $sub->cancel_at->format('d M Y') }}</span>
                            </div>
                        @endif

                        <div class="flex items-baseline gap-8 py-1">
                            <span class="w-[180px] shrink-0 text-gray-500 dark:text-gray-400">Started</span>
                            <span class="text-gray-900 dark:text-gray-100">{{ $sub->created_at->format('d M Y') }}</span>
                        </div>
                    </div>
                </div>
            </section>
        @endif

    </div>

    {{-- Right Sticky Nav --}}
    <div class="w-56 shrink-0 hidden md:block">
        <div class="sticky top-24 space-y-3">
            @if ($this->record->status === \App\Enums\DonationStatus::Succeeded)
                <div class="space-y-2 px-3 py-2">
                    <a
                        href="#"
                        wire:click="refundDonation"
                        wire:confirm="Refund {{ strtoupper($this->record->currency) }} {{ number_format((float) $this->record->gross_amount, 2) }} to {{ $this->record->donor?->name }}? This cannot be undone."
                        class="flex items-center gap-2 text-sm font-medium text-red-600 transition-colors hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
                    >
                        <x-heroicon-o-arrow-uturn-left class="size-4" />
                        Refund
                    </a>

                    <a
                        href="{{ route('donations.receipt.download', $this->record) }}"
                        target="_blank"
                        class="flex items-center gap-2 text-sm font-medium text-gray-600 transition-colors hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200"
                    >
                        <x-heroicon-o-arrow-down-tray class="size-4" />
                        Download Receipt
                    </a>
                </div>

                <div class="border-t border-gray-200 dark:border-gray-700"></div>
            @endif

            @php
                $navItems = [
                    ['id' => 'general', 'label' => 'Donation', 'icon' => 'heroicon-o-information-circle'],
                    ['id' => 'personal-info', 'label' => 'Personal Information', 'icon' => 'heroicon-o-user'],
                    ['id' => 'donor-campaign', 'label' => 'Donor & Campaign', 'icon' => 'heroicon-o-user-group'],
                    ['id' => 'payment-fee', 'label' => 'Payment & Fee', 'icon' => 'heroicon-o-banknotes'],
                    ['id' => 'insights', 'label' => 'Insights', 'icon' => 'heroicon-o-chart-pie'],
                    ['id' => 'stripe-info', 'label' => 'Stripe Info', 'icon' => 'heroicon-o-credit-card'],
                ];
                if ($this->record->subscription) {
                    $navItems[] = ['id' => 'recurring-plan', 'label' => 'Recurring Plan', 'icon' => 'heroicon-o-arrow-path'];
                }
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
</x-filament-panels::page>
