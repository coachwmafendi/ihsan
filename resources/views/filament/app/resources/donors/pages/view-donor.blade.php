@php
    use App\Filament\App\Resources\Donors\Pages\ViewDonor;
    use App\Filament\App\Resources\Donors\RelationManagers\DonationsRelationManager;
    use App\Filament\App\Resources\Donors\RelationManagers\SubscriptionsRelationManager;

    $pageClass = ViewDonor::class;
    $record = $this->getRecord();
    $hasDonations = $this->hasDonationRecords();
    $hasReceipts = $this->hasReceiptRecords();
    $hasRecurringPlans = $this->hasRecurringPlans();
    $receiptDonations = $hasReceipts ? $this->getReceiptDonations() : collect();
    $countryCode = $record->country ? strtoupper($record->country) : null;
    $countryName = $countryCode ? (\Locale::getDisplayRegion('-'.$countryCode, 'en') ?: $countryCode) : '—';
    $addressLines = collect([
        $record->address_line1,
        $record->address_line2,
        collect([$record->address_city, $record->address_state, $record->address_postal_code])->filter()->join(', '),
        $countryCode ? $countryName : null,
    ])->filter();
@endphp

<x-filament-panels::page>
    <style>
        .supporter-view-shell {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .supporter-view-main {
            min-width: 0;
            flex: 1 1 auto;
        }

        .supporter-view-nav {
            display: none;
        }

        @media (min-width: 1024px) {
            .supporter-view-shell {
                flex-direction: row;
                align-items: flex-start;
            }

            .supporter-view-nav {
                display: block;
                width: 15rem;
                flex: 0 0 15rem;
            }
        }
    </style>

    <div
        x-data="{
            activeSection: 'supporter-information',
            scrollTo(id) {
                document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' })
                this.activeSection = id
            },
        }"
        x-init="
            const observer = new IntersectionObserver(
                (entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) activeSection = entry.target.id
                    })
                },
                { rootMargin: '-20% 0px -70% 0px', threshold: 0 }
            )
            $nextTick(() => {
                $el.querySelectorAll('section[id]').forEach(el => observer.observe(el))
            })
        "
        class="supporter-view-shell"
    >
        <div class="supporter-view-main space-y-6">
            <section
                id="supporter-information"
                class="scroll-mt-24 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900"
            >
                <div class="flex flex-wrap items-center justify-between gap-4 px-8 py-6">
                    <div class="flex items-center gap-5">
                        <x-heroicon-o-user class="size-8 shrink-0 text-gray-950 dark:text-white" />
                        <div>
                            <h2 class="text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">
                                Information
                            </h2>
                            <p class="mt-1 text-base text-gray-500 dark:text-gray-400">
                                {{ $record->email }}
                            </p>
                        </div>
                    </div>

                    <span class="rounded-md bg-gray-100 px-4 py-2 text-base font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                        {{ $record->public_id ?? '#'.$record->getKey() }}
                    </span>
                </div>

                <div class="border-t border-gray-200 dark:border-gray-800">
                    <!-- Name / Email row -->
                    <div class="flex gap-6 border-b border-gray-200 px-8 py-5 dark:border-gray-800">
                        <div class="w-[240px] shrink-0">
                            <p class="text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Name</p>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-lg font-medium text-gray-950 dark:text-white">{{ $record->name }}</p>
                        </div>
                    </div>

                    <!-- Email row -->
                    <div class="flex gap-6 border-b border-gray-200 px-8 py-5 dark:border-gray-800">
                        <div class="w-[240px] shrink-0">
                            <p class="text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Email</p>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-lg font-medium text-gray-950 dark:text-white">{{ $record->email }}</p>
                        </div>
                    </div>

                    <!-- Phone row -->
                    <div class="flex gap-6 border-b border-gray-200 px-8 py-5 dark:border-gray-800">
                        <div class="w-[240px] shrink-0">
                            <p class="text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Phone</p>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-lg font-medium text-gray-950 dark:text-white">{{ $record->phone ?: '—' }}</p>
                        </div>
                    </div>

                    <!-- Country row -->
                    <div class="flex gap-6 border-b border-gray-200 px-8 py-5 dark:border-gray-800">
                        <div class="w-[240px] shrink-0">
                            <p class="text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Country</p>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-lg font-medium text-gray-950 dark:text-white">{{ $countryName }}</p>
                        </div>
                    </div>

                    <!-- Mailing Address row -->
                    <div class="flex gap-6 px-8 py-5">
                        <div class="w-[240px] shrink-0">
                            <p class="text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Mailing Address</p>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="text-lg font-medium leading-8 text-gray-950 dark:text-white">
                                @forelse ($addressLines as $line)
                                    <p>{{ $line }}</p>
                                @empty
                                    <p>—</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            @if ($hasReceipts)
                <section
                    id="receipts-section"
                    class="scroll-mt-24 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900"
                >
                    <div class="flex items-center gap-3 border-b border-gray-200 px-6 py-5 dark:border-gray-800">
                        <x-heroicon-o-receipt-percent class="size-6 text-gray-900 dark:text-gray-100" />
                        <h2 class="text-xl font-semibold tracking-tight text-gray-950 dark:text-white">
                            Receipts
                        </h2>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[760px] divide-y divide-gray-200 text-left dark:divide-gray-800">
                            <thead class="bg-gray-50 dark:bg-gray-950/40">
                                <tr>
                                    <th class="w-16 px-6 py-3"></th>
                                    <th class="px-6 py-3 text-sm font-semibold text-gray-950 dark:text-gray-100">Receipt number</th>
                                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-950 dark:text-gray-100">Amount</th>
                                    <th class="px-6 py-3 text-sm font-semibold text-gray-950 dark:text-gray-100">Donation date</th>
                                    <th class="px-6 py-3 text-sm font-semibold text-gray-950 dark:text-gray-100">Issue date</th>
                                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-950 dark:text-gray-100"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                                @foreach ($receiptDonations as $donation)
                                    <tr class="hover:bg-gray-50/80 dark:hover:bg-gray-800/50">
                                        <td class="px-6 py-4">
                                            @if ($donation->status === \App\Enums\DonationStatus::Succeeded)
                                                <x-heroicon-o-check class="size-5 text-success-600 dark:text-success-400" />
                                            @else
                                                <span class="inline-flex size-5 items-center justify-center rounded-full bg-gray-100 text-[10px] font-semibold uppercase text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                                                    {{ str($donation->status->value)->substr(0, 1) }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-sm font-medium text-gray-950 dark:text-white">
                                            {{ $donation->invoice_number }}
                                        </td>
                                        <td class="px-6 py-4 text-right text-sm font-semibold tabular-nums text-gray-950 dark:text-white">
                                            {{ $donation->currency !== 'myr' && $donation->base_amount ? '≈ ' : '' }}MYR {{ number_format((float) ($donation->base_amount ?? $donation->gross_amount), 2) }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">
                                            {{ $donation->created_at?->format('M j, Y') }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">
                                            {{ ($donation->receipt_sent_at ?? $donation->created_at)?->format('M j, Y') }}
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            @if ($donation->status === \App\Enums\DonationStatus::Succeeded)
                                                <a
                                                    href="{{ route('donations.receipt.download', $donation) }}"
                                                    target="_blank"
                                                    rel="noopener"
                                                    class="inline-flex items-center gap-2 text-sm font-medium text-gray-500 transition hover:text-gray-950 dark:text-gray-400 dark:hover:text-white"
                                                >
                                                    <x-heroicon-o-arrow-down-tray class="size-4" />
                                                    Download
                                                </a>
                                            @else
                                                <span class="text-sm font-medium text-gray-400 dark:text-gray-500">
                                                    Unavailable
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            @endif

            @if ($hasDonations)
                <div id="donations-section" class="scroll-mt-24">
                    @livewire(DonationsRelationManager::class, [
                        'ownerRecord' => $record,
                        'pageClass' => $pageClass,
                    ], key('supporter-donations-'.$record->getKey()))
                </div>
            @endif

            @if ($hasRecurringPlans)
                <div id="recurring-plans-section" class="scroll-mt-24">
                    @livewire(SubscriptionsRelationManager::class, [
                        'ownerRecord' => $record,
                        'pageClass' => $pageClass,
                    ], key('supporter-recurring-plans-'.$record->getKey()))
                </div>
            @endif
        </div>

        <aside class="supporter-view-nav">
            <div class="sticky top-24 space-y-1 rounded-lg border border-gray-200 bg-white p-2 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <button
                    type="button"
                    x-on:click="scrollTo('supporter-information')"
                    x-bind:class="activeSection === 'supporter-information'
                        ? 'bg-gray-950 text-white dark:bg-white dark:text-gray-950'
                        : 'text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-800'"
                    class="w-full rounded-md px-3 py-2 text-left text-sm font-medium transition"
                >
                    Information
                </button>

                @if ($hasReceipts)
                    <button
                        type="button"
                        x-on:click="scrollTo('receipts-section')"
                        x-bind:class="activeSection === 'receipts-section'
                            ? 'bg-gray-950 text-white dark:bg-white dark:text-gray-950'
                            : 'text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-800'"
                        class="w-full rounded-md px-3 py-2 text-left text-sm font-medium transition"
                    >
                        Receipts
                    </button>
                @endif

                @if ($hasDonations)
                    <button
                        type="button"
                        x-on:click="scrollTo('donations-section')"
                        x-bind:class="activeSection === 'donations-section'
                            ? 'bg-gray-950 text-white dark:bg-white dark:text-gray-950'
                            : 'text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-800'"
                        class="w-full rounded-md px-3 py-2 text-left text-sm font-medium transition"
                    >
                        Donations
                    </button>
                @endif

                @if ($hasRecurringPlans)
                    <button
                        type="button"
                        x-on:click="scrollTo('recurring-plans-section')"
                        x-bind:class="activeSection === 'recurring-plans-section'
                            ? 'bg-gray-950 text-white dark:bg-white dark:text-gray-950'
                            : 'text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-800'"
                        class="w-full rounded-md px-3 py-2 text-left text-sm font-medium transition"
                    >
                        Recurring
                    </button>
                @endif
            </div>
        </aside>
    </div>
</x-filament-panels::page>
