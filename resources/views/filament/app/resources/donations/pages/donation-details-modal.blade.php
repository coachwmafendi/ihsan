<div
    x-data="{
        activeSection: 'general',
        scrollTo(id) {
            const el = document.getElementById(id);
            const container = $el.closest('.fi-modal-window-ctn') || $el.closest('.fi-modal') || $el.parentElement;
            if (el && container) {
                const containerTop = container.getBoundingClientRect().top;
                const elTop = el.getBoundingClientRect().top;
                const targetScroll = container.scrollTop + (elTop - containerTop) - 24;
                container.scrollTo({ top: targetScroll, behavior: 'smooth' });
            }
        }
    }"
    x-init="$nextTick(() => {
        setTimeout(() => {
            const container = $el.closest('.fi-modal-window-ctn') || $el.closest('.fi-modal') || $el.parentElement;
            const sections = $el.querySelectorAll('section[id]');

            const updateActive = () => {
                const containerTop = container.getBoundingClientRect().top + 120;
                let current = sections[0]?.id || 'general';

                for (const section of sections) {
                    const sectionTop = section.getBoundingClientRect().top;
                    if (sectionTop <= containerTop) {
                        current = section.id;
                    } else {
                        break;
                    }
                }

                activeSection = current;
            };

            container?.addEventListener('scroll', updateActive, { passive: true });
            updateActive();
        }, 300);
    })"
    class="flex gap-6"
>
    {{-- Left Content --}}
    <div class="flex-1 min-w-0 space-y-6 pb-4">

        {{-- General --}}
        <section id="general" class="scroll-mt-6">
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center gap-2">
                    <x-heroicon-o-information-circle class="size-5 text-gray-400 dark:text-gray-500" />
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">General</h3>
                </div>
                <div class="px-6 py-4 space-y-2 text-sm">
                    {{-- Amount --}}
                    <div class="flex items-baseline gap-8 py-1">
                        <span class="w-[120px] shrink-0 text-gray-500 dark:text-gray-400">Amount</span>
                        <span class="text-gray-900 dark:text-gray-100">
                            @if ($record->currency !== 'myr' && $record->base_amount)
                                {{ strtoupper($record->currency) }} {{ number_format((float) $record->gross_amount, 2) }}
                                <span class="text-gray-400 dark:text-gray-500 ml-1.5">≈ MYR {{ number_format((float) $record->base_amount, 2) }}</span>
                            @else
                                {{ strtoupper($record->currency) }} {{ number_format((float) $record->gross_amount, 2) }}
                            @endif
                        </span>
                    </div>

                    {{-- Status --}}
                    <div class="flex items-center gap-8 py-1">
                        <span class="w-[120px] shrink-0 text-gray-500 dark:text-gray-400">Status</span>
                        <span>
                            @php
                                $statusColor = match (ucfirst($record->status->value)) {
                                    'Pending' => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
                                    'Succeeded' => 'bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                                    'Failed' => 'bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                                    'Refunded' => 'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                                    default => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
                                };
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium {{ $statusColor }}">
                                {{ ucfirst($record->status->value) }}
                            </span>
                        </span>
                    </div>

                    {{-- Type --}}
                    <div class="flex items-center gap-8 py-1">
                        <span class="w-[120px] shrink-0 text-gray-500 dark:text-gray-400">Type</span>
                        <span class="text-gray-900 dark:text-gray-100">{{ str($record->type->value)->headline() }}</span>
                    </div>

                    {{-- Date --}}
                    <div class="flex items-baseline gap-8 py-1">
                        <span class="w-[120px] shrink-0 text-gray-500 dark:text-gray-400">Date</span>
                        <span class="text-gray-900 dark:text-gray-100">{{ $record->created_at->format('d M Y, h:i A') }}</span>
                    </div>

                    {{-- Receipt No. --}}
                    <div class="flex items-center gap-8 py-1">
                        <span class="w-[120px] shrink-0 text-gray-500 dark:text-gray-400">Receipt No.</span>
                        <div class="flex items-center gap-2">
                            <span class="text-gray-900 dark:text-gray-100">{{ $record->invoice_number }}</span>
                            @if ($record->status->value === 'succeeded')
                                <a href="{{ route('donations.receipt.download', $record) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-xs font-medium text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                                    <x-heroicon-o-arrow-down-tray class="size-3" />
                                    Download Receipt
                                </a>
                            @endif
                        </div>
                    </div>

                    {{-- Donation ID --}}
                    <div class="flex items-center gap-8 py-1">
                        <span class="w-[120px] shrink-0 text-gray-500 dark:text-gray-400">Donation ID</span>
                        <div class="flex items-center gap-1.5">
                            <span class="text-gray-900 dark:text-gray-100">{{ $record->id }}</span>
                            <button
                                type="button"
                                @click="navigator.clipboard.writeText('{{ $record->id }}'); $dispatch('notify', { message: 'Copied' })"
                                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                                title="Copy"
                            >
                                <x-heroicon-o-clipboard-document class="size-3.5" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Donor & Campaign --}}
        <section id="donor-campaign" class="scroll-mt-6">
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center gap-2">
                    <x-heroicon-o-user-group class="size-5 text-gray-400 dark:text-gray-500" />
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Donor & Campaign</h3>
                </div>
                <div class="px-6 py-4 space-y-2 text-sm">
                    {{-- Supporter --}}
                    <div class="flex items-center gap-8 py-1">
                        <span class="w-[120px] shrink-0 text-gray-500 dark:text-gray-400">Supporter</span>
                        <div class="flex items-center gap-1.5">
                            <span class="text-gray-900 dark:text-gray-100">{{ $record->donor?->name ?? '—' }}</span>
                            @php
                                $deviceIcon = match ($record->device_type) {
                                    'mobile' => 'heroicon-o-device-phone-mobile',
                                    'tablet' => 'heroicon-o-device-tablet',
                                    'desktop' => 'heroicon-o-computer-desktop',
                                    default => null,
                                };
                                $deviceLabel = match ($record->device_type) {
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

                    {{-- Campaign --}}
                    <div class="flex items-center gap-8 py-1">
                        <span class="w-[120px] shrink-0 text-gray-500 dark:text-gray-400">Campaign</span>
                        <span class="text-gray-900 dark:text-gray-100">{{ $record->campaign?->title ?? '—' }}</span>
                    </div>

                    {{-- Email --}}
                    <div class="flex items-center gap-8 py-1">
                        <span class="w-[120px] shrink-0 text-gray-500 dark:text-gray-400">Email</span>
                        <div class="flex items-center gap-1.5">
                            <span class="text-gray-900 dark:text-gray-100">{{ $record->donor?->email ?? '—' }}</span>
                            @if ($record->donor?->email)
                                <button
                                    type="button"
                                    @click="navigator.clipboard.writeText('{{ $record->donor->email }}'); $dispatch('notify', { message: 'Copied' })"
                                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                                    title="Copy"
                                >
                                    <x-heroicon-o-clipboard-document class="size-3.5" />
                                </button>
                            @endif
                        </div>
                    </div>

                    {{-- Phone --}}
                    <div class="flex items-center gap-8 py-1">
                        <span class="w-[120px] shrink-0 text-gray-500 dark:text-gray-400">Phone</span>
                        <span class="text-gray-900 dark:text-gray-100">{{ $record->donor?->phone ?? '—' }}</span>
                    </div>

                    {{-- Payment Method --}}
                    <div class="flex items-center gap-8 py-1">
                        <span class="w-[120px] shrink-0 text-gray-500 dark:text-gray-400">Payment Method</span>
                        <span class="flex items-center gap-2 text-gray-900 dark:text-gray-100">
                            @php
                                $brand = strtolower($record->payment_method_brand ?? '');
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
                                <x-dynamic-component :component="$iconComponent" class="w-8 h-4" />
                            @else
                                <x-heroicon-o-credit-card class="w-5 h-5 text-gray-400" />
                            @endif
                            <span>
                                {{ collect([
                                    $record->payment_method_brand ? str($record->payment_method_brand)->headline()->toString() : null,
                                    $record->payment_method_last4 ? '•••• '.$record->payment_method_last4 : null,
                                ])->filter()->join(' ') ?: '—' }}
                            </span>
                        </span>
                    </div>

                    {{-- Anonymous --}}
                    <div class="flex items-center gap-8 py-1">
                        <span class="w-[120px] shrink-0 text-gray-500 dark:text-gray-400">Anonymous</span>
                        <span class="text-gray-900 dark:text-gray-100">{{ $record->is_anonymous ? 'Yes' : 'No' }}</span>
                    </div>

                    {{-- Element --}}
                    <div class="flex items-center gap-8 py-1">
                        <span class="w-[120px] shrink-0 text-gray-500 dark:text-gray-400">Element</span>
                        <span class="text-gray-900 dark:text-gray-100">{{ $record->element_label ?? '—' }}</span>
                    </div>

                    {{-- URL --}}
                    <div class="flex items-center gap-8 py-1">
                        <span class="w-[120px] shrink-0 text-gray-500 dark:text-gray-400">URL</span>
                        <div class="flex items-center gap-1.5 min-w-0">
                            <span class="text-gray-900 dark:text-gray-100 truncate">{{ $record->page_url ?? '—' }}</span>
                            @if ($record->page_url)
                                <button
                                    type="button"
                                    @click="navigator.clipboard.writeText('{{ $record->page_url }}'); $dispatch('notify', { message: 'Copied' })"
                                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors shrink-0"
                                    title="Copy"
                                >
                                    <x-heroicon-o-clipboard-document class="size-3.5" />
                                </button>
                            @endif
                        </div>
                    </div>

                    {{-- Message --}}
                    @if ($record->donor_message)
                        <div class="flex items-start gap-8 py-1">
                            <span class="w-[120px] shrink-0 text-gray-500 dark:text-gray-400 pt-0.5">Message</span>
                            <span class="text-gray-900 dark:text-gray-100 whitespace-pre-wrap">{{ $record->donor_message }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </section>

        {{-- Payment & Fee --}}
        <section id="payment-fee" class="scroll-mt-6">
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center gap-2">
                    <x-heroicon-o-banknotes class="size-5 text-gray-400 dark:text-gray-500" />
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Payment & Fee</h3>
                </div>
                <div class="px-6 py-4 space-y-2 text-sm">
                    {{-- Gross --}}
                    <div class="flex items-baseline gap-8 py-1">
                        <span class="w-[120px] shrink-0 text-gray-500 dark:text-gray-400">Gross</span>
                        <span class="text-gray-900 dark:text-gray-100">
                            @if ($record->currency !== 'myr' && $record->base_amount)
                                {{ strtoupper($record->currency) }} {{ number_format((float) $record->gross_amount, 2) }}
                                <span class="text-gray-400 dark:text-gray-500 ml-1.5">≈ MYR {{ number_format((float) $record->base_amount, 2) }}</span>
                            @else
                                MYR {{ number_format((float) $record->gross_amount, 2) }}
                            @endif
                        </span>
                    </div>

                    {{-- Stripe Fee --}}
                    <div class="flex items-center gap-8 py-1">
                        <span class="w-[120px] shrink-0 text-gray-500 dark:text-gray-400">Stripe Fee</span>
                        <span class="text-gray-900 dark:text-gray-100">MYR {{ number_format((float) ($record->stripe_fee ?? 0), 2) }}</span>
                    </div>

                    {{-- Processing Fee --}}
                    <div class="flex items-center gap-8 py-1">
                        <span class="w-[120px] shrink-0 text-gray-500 dark:text-gray-400">Processing Fee</span>
                        <span class="text-gray-900 dark:text-gray-100">MYR {{ number_format((float) ($record->processing_fee ?? 0), 2) }}</span>
                    </div>

                    <div class="border-t border-gray-100 dark:border-gray-800 my-2"></div>

                    {{-- Total Fees --}}
                    <div class="flex items-center gap-8 py-1">
                        <span class="w-[120px] shrink-0 text-gray-500 dark:text-gray-400">Total Fees</span>
                        <span class="text-gray-900 dark:text-gray-100">MYR {{ number_format((float) ($record->stripe_fee ?? 0) + (float) ($record->processing_fee ?? 0), 2) }}</span>
                    </div>

                    {{-- Net --}}
                    <div class="flex items-center gap-8 py-1">
                        <span class="w-[120px] shrink-0 text-gray-500 dark:text-gray-400">Net</span>
                        <span class="text-gray-900 dark:text-gray-100 font-semibold">MYR {{ number_format((float) $record->net_amount, 2) }}</span>
                    </div>

                    {{-- Fee Covered by Donor --}}
                    <div class="flex items-center gap-8 py-1">
                        <span class="w-[120px] shrink-0 text-gray-500 dark:text-gray-400">Fee Covered by Donor</span>
                        @php
                            $feeCovered = (float) ($record->donor_fee_covered ?? 0);
                        @endphp
                        @if ($feeCovered > 0)
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">
                                MYR {{ number_format($feeCovered, 2) }}
                            </span>
                        @else
                            <span class="text-gray-500 dark:text-gray-400">No</span>
                        @endif
                    </div>
                </div>
            </div>
        </section>

        {{-- Insights --}}
        <section id="insights" class="scroll-mt-6">
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center gap-2">
                    <x-heroicon-o-chart-pie class="size-5 text-gray-400 dark:text-gray-500" />
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Insights</h3>
                </div>
                <div class="px-6 py-4 space-y-2 text-sm">
                    {{-- IP Address --}}
                    <div class="flex items-center gap-8 py-1">
                        <span class="w-[120px] shrink-0 text-gray-500 dark:text-gray-400">IP Address</span>
                        <div class="flex items-center gap-1.5">
                            <span class="text-gray-900 dark:text-gray-100">{{ $record->ip_address ?? '—' }}</span>
                            @if ($record->ip_address)
                                <button
                                    type="button"
                                    @click="navigator.clipboard.writeText('{{ $record->ip_address }}'); $dispatch('notify', { message: 'Copied' })"
                                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                                    title="Copy"
                                >
                                    <x-heroicon-o-clipboard-document class="size-3.5" />
                                </button>
                            @endif
                        </div>
                    </div>

                    {{-- Device --}}
                    <div class="flex items-center gap-8 py-1">
                        <span class="w-[120px] shrink-0 text-gray-500 dark:text-gray-400">Device</span>
                        <div class="flex items-center gap-1.5">
                            @php
                                $deviceIcon2 = match ($record->device_type) {
                                    'mobile' => 'heroicon-o-device-phone-mobile',
                                    'tablet' => 'heroicon-o-device-tablet',
                                    'desktop' => 'heroicon-o-computer-desktop',
                                    default => null,
                                };
                                $deviceLabel2 = match ($record->device_type) {
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

                    {{-- Browser --}}
                    <div class="flex items-center gap-8 py-1">
                        <span class="w-[120px] shrink-0 text-gray-500 dark:text-gray-400">Browser</span>
                        <span class="text-gray-900 dark:text-gray-100">{{ $record->browser ?? '—' }}</span>
                    </div>

                    {{-- OS --}}
                    <div class="flex items-center gap-8 py-1">
                        <span class="w-[120px] shrink-0 text-gray-500 dark:text-gray-400">OS</span>
                        <span class="text-gray-900 dark:text-gray-100">{{ $record->os ?? '—' }}</span>
                    </div>

                    {{-- Location --}}
                    <div class="flex items-center gap-8 py-1">
                        <span class="w-[120px] shrink-0 text-gray-500 dark:text-gray-400">Location</span>
                        <span class="text-gray-900 dark:text-gray-100">
                            @if ($record->geo_city && $record->geo_region)
                                {{ $record->geo_city }}, {{ $record->geo_region }}
                            @elseif ($record->geo_city)
                                {{ $record->geo_city }}
                            @else
                                —
                            @endif
                        </span>
                    </div>
                </div>
            </div>
        </section>

        {{-- Stripe Info --}}
        <section id="stripe-info" class="scroll-mt-6">
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center gap-2">
                    <x-heroicon-o-credit-card class="size-5 text-gray-400 dark:text-gray-500" />
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Stripe Info</h3>
                </div>
                <div class="px-6 py-4 space-y-2 text-sm">
                    {{-- Payment Intent ID --}}
                    <div class="flex items-center gap-8 py-1">
                        <span class="w-[120px] shrink-0 text-gray-500 dark:text-gray-400">Payment Intent ID</span>
                        <div class="flex items-center gap-1.5 min-w-0">
                            <span class="text-gray-900 dark:text-gray-100 font-mono truncate">{{ $record->stripe_payment_intent_id ?? '—' }}</span>
                            @if ($record->stripe_payment_intent_id)
                                <button
                                    type="button"
                                    @click="navigator.clipboard.writeText('{{ $record->stripe_payment_intent_id }}'); $dispatch('notify', { message: 'Copied' })"
                                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors shrink-0"
                                    title="Copy"
                                >
                                    <x-heroicon-o-clipboard-document class="size-3.5" />
                                </button>
                            @endif
                        </div>
                    </div>

                    {{-- Charge ID --}}
                    <div class="flex items-center gap-8 py-1">
                        <span class="w-[120px] shrink-0 text-gray-500 dark:text-gray-400">Charge ID</span>
                        <div class="flex items-center gap-1.5 min-w-0">
                            <span class="text-gray-900 dark:text-gray-100 font-mono truncate">{{ $record->stripe_charge_id ?? '—' }}</span>
                            @if ($record->stripe_charge_id)
                                <button
                                    type="button"
                                    @click="navigator.clipboard.writeText('{{ $record->stripe_charge_id }}'); $dispatch('notify', { message: 'Copied' })"
                                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors shrink-0"
                                    title="Copy"
                                >
                                    <x-heroicon-o-clipboard-document class="size-3.5" />
                                </button>
                            @endif
                        </div>
                    </div>

                    {{-- Subscription ID --}}
                    @if ($record->subscription_id)
                        <div class="flex items-center gap-8 py-1">
                            <span class="w-[120px] shrink-0 text-gray-500 dark:text-gray-400">Subscription ID</span>
                            <div class="flex items-center gap-1.5 min-w-0">
                                <span class="text-gray-900 dark:text-gray-100 font-mono truncate">{{ $record->subscription_id }}</span>
                                <button
                                    type="button"
                                    @click="navigator.clipboard.writeText('{{ $record->subscription_id }}'); $dispatch('notify', { message: 'Copied' })"
                                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors shrink-0"
                                    title="Copy"
                                >
                                    <x-heroicon-o-clipboard-document class="size-3.5" />
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </section>

    </div>

    {{-- Right Sticky Menu --}}
    <div class="w-44 shrink-0 hidden md:block">
        <div class="sticky top-24 space-y-1">
            @if ($record->status->value === 'succeeded')
                <div class="pb-2 mb-2 border-b border-gray-100 dark:border-gray-800 space-y-1">
                    <a
                        href="{{ route('donations.receipt.download', $record) }}"
                        target="_blank"
                        rel="noopener"
                        class="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-50 dark:text-gray-400 dark:hover:bg-gray-800/50 transition-colors"
                    >
                        <x-heroicon-o-arrow-down-tray class="size-4 shrink-0" />
                        Download Receipt
                    </a>
                    <button
                        type="button"
                        x-data
                        @click="$el.closest('.fi-modal')?.closest('body')?.querySelector('table tbody tr button.fi-color-danger')?.click()"
                        class="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20 transition-colors"
                    >
                        <x-heroicon-o-arrow-uturn-left class="size-4 shrink-0" />
                        Refund
                    </button>
                </div>
            @endif

            @foreach ([
                ['id' => 'general', 'label' => 'General', 'icon' => 'heroicon-o-information-circle'],
                ['id' => 'donor-campaign', 'label' => 'Donor & Campaign', 'icon' => 'heroicon-o-user-group'],
                ['id' => 'payment-fee', 'label' => 'Payment & Fee', 'icon' => 'heroicon-o-banknotes'],
                ['id' => 'insights', 'label' => 'Insights', 'icon' => 'heroicon-o-chart-pie'],
                ['id' => 'stripe-info', 'label' => 'Stripe Info', 'icon' => 'heroicon-o-credit-card'],
            ] as $item)
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
