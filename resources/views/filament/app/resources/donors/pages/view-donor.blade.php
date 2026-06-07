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
                position: sticky;
                top: 6rem;
                width: 15rem;
                flex: 0 0 15rem;
                align-self: flex-start;
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
                <div class="flex items-center gap-3 border-b border-gray-200 p-4 sm:px-6 dark:border-white/10">
                    <x-heroicon-o-user class="size-5 shrink-0 text-gray-950 dark:text-white" />
                    <h2 class="text-base leading-6 font-semibold text-gray-950 dark:text-white">Information</h2>
                    <button
                        type="button"
                        wire:click="mountAction('editSupporter')"
                        class="ml-auto text-sm font-medium text-primary-600 transition hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300"
                    >
                        Edit
                    </button>
                </div>

                <div class="border-t border-gray-100 px-6 py-4 space-y-2 text-sm dark:border-gray-800">
                    <div class="flex items-baseline gap-8 py-1">
                        <span class="w-[180px] shrink-0 text-gray-500 dark:text-gray-400">Name</span>
                        <span class="text-gray-900 dark:text-gray-100">{{ str($record->name)->title() }}</span>
                    </div>

                    <div class="flex items-baseline gap-8 py-1">
                        <span class="w-[180px] shrink-0 text-gray-500 dark:text-gray-400">Email</span>
                        <span class="text-gray-900 dark:text-gray-100">{{ $record->email }}</span>
                    </div>

                    <div class="flex items-baseline gap-8 py-1">
                        <span class="w-[180px] shrink-0 text-gray-500 dark:text-gray-400">Phone</span>
                        <span class="text-gray-900 dark:text-gray-100">{{ $record->phone ?: '—' }}</span>
                    </div>

                    <div class="flex items-baseline gap-8 py-1">
                        <span class="w-[180px] shrink-0 text-gray-500 dark:text-gray-400">Country</span>
                        <span class="text-gray-900 dark:text-gray-100">{{ $countryName }}</span>
                    </div>

                    <div class="flex items-baseline gap-8 py-1">
                        <span class="w-[180px] shrink-0 text-gray-500 dark:text-gray-400">Mailing Address</span>
                        <span class="leading-6 text-gray-900 dark:text-gray-100">
                            @forelse ($addressLines as $line)
                                <span class="block">{{ $line }}</span>
                            @empty
                                —
                            @endforelse
                        </span>
                    </div>
                </div>
            </section>

            @if ($hasDonations)
                <section id="donations-section" class="scroll-mt-24">
                    @livewire(DonationsRelationManager::class, [
                        'ownerRecord' => $record,
                        'pageClass' => $pageClass,
                    ], key('supporter-donations-'.$record->getKey()))
                </section>
            @endif

            @if ($hasRecurringPlans)
                <section id="recurring-plans-section" class="scroll-mt-24">
                    @livewire(SubscriptionsRelationManager::class, [
                        'ownerRecord' => $record,
                        'pageClass' => $pageClass,
                    ], key('supporter-recurring-plans-'.$record->getKey()))
                </section>
            @endif

            @if ($hasReceipts)
                <section
                    id="receipts-section"
                    class="scroll-mt-24 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900"
                >
                    <div class="flex items-center gap-3 border-b border-gray-200 p-4 sm:px-6 dark:border-white/10">
                        <x-heroicon-o-receipt-percent class="size-5 shrink-0 text-gray-950 dark:text-white" />
                        <h2 class="text-base leading-6 font-semibold text-gray-950 dark:text-white">Receipts</h2>
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
        </div>

        <aside class="supporter-view-nav space-y-4">
            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <a
                    href="{{ \App\Filament\App\Pages\VirtualTerminal::getUrl(['vt-supporter' => $record->public_id]) }}"
                    class="flex items-center gap-3 px-4 py-3 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-800"
                >
                    <x-heroicon-o-currency-dollar class="size-5 shrink-0" />
                    Make donation
                </a>

                <div class="border-t border-gray-200 dark:border-gray-800"></div>

                <a
                    href="{{ $this->getDonorPortalUrl() }}"
                    target="_blank"
                    rel="noopener"
                    class="flex items-center gap-3 px-4 py-3 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-800"
                >
                    <x-heroicon-o-face-smile class="size-5 shrink-0" />
                    Open Donor Portal
                </a>
            </div>

            <div class="space-y-1 rounded-lg border border-gray-200 bg-white p-2 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <button
                    type="button"
                    x-on:click="scrollTo('supporter-information')"
                    x-bind:class="activeSection === 'supporter-information'
                        ? 'bg-gray-950 text-white dark:bg-white dark:text-gray-950'
                        : 'text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-800'"
                    class="flex w-full items-center gap-2.5 rounded-md px-3 py-2 text-left text-sm font-medium transition"
                >
                    <x-heroicon-o-user class="size-4 shrink-0" />
                    Information
                </button>

                @if ($hasDonations)
                    <button
                        type="button"
                        x-on:click="scrollTo('donations-section')"
                        x-bind:class="activeSection === 'donations-section'
                            ? 'bg-gray-950 text-white dark:bg-white dark:text-gray-950'
                            : 'text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-800'"
                        class="flex w-full items-center gap-2.5 rounded-md px-3 py-2 text-left text-sm font-medium transition"
                    >
                        <x-heroicon-o-currency-dollar class="size-4 shrink-0" />
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
                        class="flex w-full items-center gap-2.5 rounded-md px-3 py-2 text-left text-sm font-medium transition"
                    >
                        <x-heroicon-o-arrow-path class="size-4 shrink-0" />
                        Recurring
                    </button>
                @endif

                @if ($hasReceipts)
                    <button
                        type="button"
                        x-on:click="scrollTo('receipts-section')"
                        x-bind:class="activeSection === 'receipts-section'
                            ? 'bg-gray-950 text-white dark:bg-white dark:text-gray-950'
                            : 'text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-800'"
                        class="flex w-full items-center gap-2.5 rounded-md px-3 py-2 text-left text-sm font-medium transition"
                    >
                        <x-heroicon-o-receipt-percent class="size-4 shrink-0" />
                        Receipts
                    </button>
                @endif
            </div>
        </aside>
    </div>
</x-filament-panels::page>
