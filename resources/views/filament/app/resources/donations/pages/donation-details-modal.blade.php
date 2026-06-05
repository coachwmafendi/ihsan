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
                <div class="px-0">
                    {{-- Amount --}}
                    <div class="flex items-baseline px-5 py-3 gap-3">
                        <p class="w-40 text-sm text-gray-500 dark:text-gray-400 shrink-0">Donation amount</p>
                        <div class="flex-1 text-sm text-gray-900 dark:text-gray-100">
                            @if ($record->currency !== 'myr' && $record->base_amount)
                                {{ strtoupper($record->currency) }} {{ number_format((float) $record->gross_amount, 2) }}
                                <span class="text-gray-400 dark:text-gray-500 ml-2">≈ MYR {{ number_format((float) $record->base_amount, 2) }}</span>
                            @else
                                {{ strtoupper($record->currency) }} {{ number_format((float) $record->gross_amount, 2) }}
                            @endif
                        </div>
                    </div>

                    {{-- Donation ID --}}
                    <div class="flex items-center px-5 py-3 gap-3">
                        <p class="w-40 text-sm text-gray-500 dark:text-gray-400 shrink-0">Donation ID</p>
                        <div class="flex-1 flex items-center gap-1.5 group">
                            <p class="text-sm text-gray-900 dark:text-gray-100">{{ $record->invoice_number }}</p>
                            <button
                                type="button"
                                @click="navigator.clipboard.writeText('{{ $record->invoice_number }}'); $dispatch('notify', { message: 'Copied' })"
                                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                                title="Copy"
                            >
                                <x-heroicon-o-clipboard-document class="size-3.5" />
                            </button>
                        </div>
                    </div>

                    {{-- Status --}}
                    <div class="flex items-center px-5 py-3 gap-3">
                        <p class="w-40 text-sm text-gray-500 dark:text-gray-400 shrink-0">Status</p>
                        <div class="flex-1">
                            @php
                                $statusColor = match (ucfirst($record->status->value)) {
                                    'Pending' => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
                                    'Succeeded' => 'bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                                    'Failed' => 'bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                                    'Refunded' => 'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                                    default => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
                                };
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium {{ $statusColor }}">
                                {{ ucfirst($record->status->value) }}
                            </span>
                        </div>
                    </div>

                    {{-- Supporter --}}
                    <div class="flex items-center px-5 py-3 gap-3">
                        <p class="w-40 text-sm text-gray-500 dark:text-gray-400 shrink-0">Supporter</p>
                        <div class="flex-1">
                            <a href="#" class="text-sm text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300">
                                {{ $record->donor?->name ?? '—' }}
                            </a>
                        </div>
                    </div>

                    {{-- Campaign --}}
                    <div class="flex items-baseline px-5 py-3 gap-3">
                        <p class="w-40 text-sm text-gray-500 dark:text-gray-400 shrink-0">Campaign</p>
                        <div class="flex-1">
                            <a href="#" class="text-sm text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300">
                                {{ $record->campaign?->title ?? '—' }}
                            </a>
                        </div>
                    </div>

                    {{-- Element --}}
                    <div class="flex items-center px-5 py-3 gap-3">
                        <p class="w-40 text-sm text-gray-500 dark:text-gray-400 shrink-0">Element</p>
                        <p class="flex-1 text-sm text-gray-900 dark:text-gray-100">{{ $record->element_label ?? '—' }}</p>
                    </div>

                    {{-- Donation date --}}
                    <div class="flex items-center px-5 py-3 gap-3">
                        <p class="w-40 text-sm text-gray-500 dark:text-gray-400 shrink-0">Donation date</p>
                        <p class="flex-1 text-sm text-gray-900 dark:text-gray-100">{{ $record->created_at->format('M d, Y, h:i A') }}</p>
                    </div>

                    {{-- Success date (same as created if succeeded) --}}
                    @if ($record->status->value !== 'pending')
                        <div class="flex items-center px-5 py-3 gap-3">
                            <p class="w-40 text-sm text-gray-500 dark:text-gray-400 shrink-0">Success date</p>
                            <p class="flex-1 text-sm text-gray-900 dark:text-gray-100">{{ $record->created_at->format('M d, Y, h:i A') }}</p>
                        </div>
                    @endif

                    {{-- Frequency --}}
                    <div class="flex items-center px-5 py-3 gap-3">
                        <p class="w-40 text-sm text-gray-500 dark:text-gray-400 shrink-0">Frequency</p>
                        <p class="flex-1 text-sm text-gray-900 dark:text-gray-100">{{ $record->type->value === 'recurring' ? 'Recurring' : 'Once' }}</p>
                    </div>

                    @if ($record->status->value === 'refunded')
                        {{-- Refunded At --}}
                        <div class="flex items-center px-5 py-3 gap-3 border-t border-gray-100 dark:border-gray-800">
                            <p class="w-40 text-sm text-gray-500 dark:text-gray-400 shrink-0">Refunded date</p>
                            <p class="flex-1 text-sm text-gray-900 dark:text-gray-100">{{ $record->refunded_at->format('M d, Y, h:i A') }}</p>
                        </div>
                    @endif
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
                <div class="p-5 grid grid-cols-2 gap-x-6 gap-y-4">
                    {{-- Supporter --}}
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Supporter</p>
                        <div class="flex items-center gap-1.5">
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $record->donor?->name ?? '—' }}</p>
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
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Campaign</p>
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $record->campaign?->title ?? '—' }}</p>
                    </div>

                    {{-- Email --}}
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Email</p>
                        <div class="flex items-center gap-1.5 group">
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $record->donor?->email ?? '—' }}</p>
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
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Phone</p>
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $record->donor?->phone ?? '—' }}</p>
                    </div>

                    {{-- Payment Method --}}
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Payment Method</p>
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                            {{ collect([
                                $record->payment_method_brand ? str($record->payment_method_brand)->headline()->toString() : null,
                                $record->payment_method_last4 ? '•••• '.$record->payment_method_last4 : null,
                            ])->filter()->join(' ') ?: '—' }}
                        </p>
                    </div>

                    {{-- Anonymous --}}
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Anonymous</p>
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $record->is_anonymous ? 'Yes' : 'No' }}</p>
                    </div>

                    {{-- Element --}}
                    <div class="col-span-2">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Element</p>
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $record->element_label ?? '—' }}</p>
                    </div>

                    {{-- Message --}}
                    @if ($record->donor_message)
                        <div class="col-span-2">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Message</p>
                            <p class="text-sm text-gray-900 dark:text-gray-100 whitespace-pre-wrap">{{ $record->donor_message }}</p>
                        </div>
                    @endif

                    {{-- URL --}}
                    <div class="col-span-2">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">URL</p>
                        <div class="flex items-center gap-1.5 group">
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ $record->page_url ?? '—' }}</p>
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
                <div class="p-5">
                    <div class="grid grid-cols-3 gap-x-6 gap-y-4 mb-4">
                        {{-- Gross --}}
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Gross</p>
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                @if ($record->currency !== 'myr' && $record->base_amount)
                                    {{ strtoupper($record->currency) }} {{ number_format((float) $record->gross_amount, 2) }}
                                    <span class="text-xs text-gray-500 dark:text-gray-400">≈ MYR {{ number_format((float) $record->base_amount, 2) }}</span>
                                @else
                                    MYR {{ number_format((float) $record->gross_amount, 2) }}
                                @endif
                            </p>
                        </div>

                        {{-- Stripe Fee --}}
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Stripe Fee</p>
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">MYR {{ number_format((float) ($record->stripe_fee ?? 0), 2) }}</p>
                        </div>

                        {{-- Processing Fee --}}
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Processing Fee</p>
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">MYR {{ number_format((float) ($record->processing_fee ?? 0), 2) }}</p>
                        </div>
                    </div>

                    <div class="border-t border-gray-100 dark:border-gray-800 pt-4 grid grid-cols-3 gap-x-6 gap-y-4">
                        {{-- Total Fees --}}
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Total Fees</p>
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">MYR {{ number_format((float) ($record->stripe_fee ?? 0) + (float) ($record->processing_fee ?? 0), 2) }}</p>
                        </div>

                        {{-- Net --}}
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Net</p>
                            <p class="text-sm font-bold text-gray-900 dark:text-white">MYR {{ number_format((float) $record->net_amount, 2) }}</p>
                        </div>

                        {{-- Fee Covered by Donor --}}
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Fee Covered by Donor</p>
                            @php
                                $feeCovered = (float) ($record->donor_fee_covered ?? 0);
                            @endphp
                            @if ($feeCovered > 0)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">
                                    MYR {{ number_format($feeCovered, 2) }}
                                </span>
                            @else
                                <span class="text-sm text-gray-500 dark:text-gray-400">No</span>
                            @endif
                        </div>
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
                <div class="p-5 grid grid-cols-2 gap-x-6 gap-y-4">
                    {{-- IP Address --}}
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">IP Address</p>
                        <div class="flex items-center gap-1.5 group">
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $record->ip_address ?? '—' }}</p>
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
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Device</p>
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
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $deviceLabel2 ?? '—' }}</p>
                        </div>
                    </div>

                    {{-- Browser --}}
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Browser</p>
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $record->browser ?? '—' }}</p>
                    </div>

                    {{-- OS --}}
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">OS</p>
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $record->os ?? '—' }}</p>
                    </div>

                    {{-- Location --}}
                    <div class="col-span-2">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Location</p>
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                            @if ($record->geo_city && $record->geo_region)
                                {{ $record->geo_city }}, {{ $record->geo_region }}
                            @elseif ($record->geo_city)
                                {{ $record->geo_city }}
                            @else
                                —
                            @endif
                        </p>
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
                <div class="p-5 grid grid-cols-2 gap-x-6 gap-y-4">
                    {{-- Payment Intent ID --}}
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Payment Intent ID</p>
                        <div class="flex items-center gap-1.5 group">
                            <p class="text-sm font-mono font-medium text-gray-900 dark:text-gray-100 truncate">{{ $record->stripe_payment_intent_id ?? '—' }}</p>
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
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Charge ID</p>
                        <div class="flex items-center gap-1.5 group">
                            <p class="text-sm font-mono font-medium text-gray-900 dark:text-gray-100 truncate">{{ $record->stripe_charge_id ?? '—' }}</p>
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
                        <div class="col-span-2">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Subscription ID</p>
                            <div class="flex items-center gap-1.5 group">
                                <p class="text-sm font-mono font-medium text-gray-900 dark:text-gray-100 truncate">{{ $record->subscription_id }}</p>
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
                        ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-400'
                        : 'text-gray-600 hover:bg-gray-50 dark:text-gray-400 dark:hover:bg-gray-800/50'"
                >
                    <x-dynamic-component :component="$item['icon']" class="size-4 shrink-0" />
                    {{ $item['label'] }}
                </button>
            @endforeach
        </div>
    </div>
</div>
