@extends('donor.layout')

@section('title', 'Donations')

@section('content')
<x-donor-skeleton>
    <x-slot:skeleton>
        <div class="mb-8">
            <div class="h-8 w-48 animate-pulse rounded-lg bg-slate-200"></div>
            <div class="mt-1 h-4 w-72 animate-pulse rounded bg-slate-100"></div>
        </div>
        <div class="mb-6 grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div class="h-24 animate-pulse rounded-xl bg-slate-100 donor-card-skeleton"></div>
            <div class="h-24 animate-pulse rounded-xl bg-slate-100 donor-card-skeleton"></div>
        </div>
        <div class="hidden sm:block rounded-xl bg-white overflow-hidden">
            <div class="h-10 animate-pulse bg-slate-100"></div>
            <div class="h-16 animate-pulse bg-slate-50 border-t border-slate-100"></div>
            <div class="h-16 animate-pulse bg-slate-50 border-t border-slate-100"></div>
        </div>
        <div class="space-y-3 sm:hidden">
            <div class="h-32 animate-pulse rounded-xl bg-slate-100 donor-card-skeleton"></div>
            <div class="h-32 animate-pulse rounded-xl bg-slate-100 donor-card-skeleton"></div>
        </div>
    </x-slot:skeleton>
    <div x-data="{
        detailOpen: false,
        detailUrl: '',
        detailHtml: '',
        detailLoading: false,
        openDetail(url) {
            this.detailOpen = true;
            this.detailLoading = true;
            this.detailHtml = '';
            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.text())
                .then(html => { this.detailHtml = html; })
                .catch(() => { this.detailHtml = '<div class=\'p-6 text-red-600\'>Unable to load details.</div>'; })
                .finally(() => { this.detailLoading = false; });
        },
        closeDetail() {
            this.detailOpen = false;
            this.detailHtml = '';
        }
    }">
        <div class="mb-8 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black tracking-tight text-slate-900 [letter-spacing:-0.02em]">Donations</h1>
                @if ($subscription !== null)
                    <p class="mt-0.5 text-xs text-slate-500">
                        Payment history for <strong>{{ $subscription->campaign->title }}</strong>
                        · {{ $subscription->displayAmount() }}/{{ $subscription->interval->value }}
                    </p>
                    <a href="{{ route('donorportal.donations', $organization) }}"
                       class="mt-2 inline-flex items-center gap-1 text-xs font-semibold text-emerald-600 hover:text-emerald-700">
                        <x-heroicon name="arrow-left" class="h-3.5 w-3.5" />
                        Back to all donations
                    </a>
                @else
                    <p class="mt-0.5 text-xs text-slate-500">Your complete giving history.</p>
                @endif
            </div>
            @if ($donationCount > 0 && $subscription === null)
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                    @if (! empty($statementYears))
                        <div x-data="{ year: '{{ $statementYears[0] }}' }" class="flex items-center gap-2">
                            <select x-model="year"
                                    class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-semibold text-slate-700 focus:border-emerald-500 focus:ring-emerald-500">
                                @foreach ($statementYears as $statementYear)
                                    <option value="{{ $statementYear }}">{{ $statementYear }}</option>
                                @endforeach
                            </select>
                            <a :href="'{{ route('donorportal.donations.annual-statement', $organization) }}?year=' + year"
                               class="inline-flex items-center gap-2 rounded-xl border border-emerald-600 px-4 py-2.5 text-sm font-bold text-emerald-700 transition hover:bg-emerald-50">
                                <x-heroicon name="document-text" class="h-4 w-4" />
                                Annual statement
                            </a>
                        </div>
                    @endif
                    <a href="{{ route('donorportal.donations.receipts.download-all', $organization) }}"
                       class="inline-flex items-center gap-2 rounded-xl px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:shadow-md bg-emerald-600">
                        <x-heroicon name="arrow-down-tray" class="h-4 w-4" />
                        Download all receipts
                    </a>
                </div>
            @endif
        </div>

        <div class="mb-6 grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div class="rounded-xl bg-white p-4 donor-card">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Total Given</p>
                @if (count($currencyBreakdown) > 1)
                    <p class="mt-1.5 text-xl font-black text-emerald-700">{{ implode(' + ', $currencyBreakdown) }}</p>
                    <p class="mt-1 text-xs text-slate-400">≈ MYR {{ number_format($totalGiven, 2) }}</p>
                @else
                    <p class="mt-1.5 text-xl font-black text-emerald-700">{{ reset($currencyBreakdown) ?? 'MYR 0.00' }}</p>
                    @if (count($currencyBreakdown) === 1 && array_key_first($currencyBreakdown) !== 'myr')
                        <p class="mt-1 text-xs text-slate-400">≈ MYR {{ number_format($totalGiven, 2) }}</p>
                    @endif
                @endif
            </div>
            <div class="rounded-xl bg-white p-4 donor-card">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Donations</p>
                <p class="mt-1.5 text-xl font-black text-slate-900">{{ $donationCount }}</p>
            </div>
        </div>

        {{-- Filters --}}
        <form method="GET" action="{{ route('donorportal.donations', $organization) }}" class="mb-5 rounded-xl bg-white p-4 donor-card">
            @if ($subscription !== null)
                <input type="hidden" name="subscription" value="{{ $subscription->public_id }}">
            @endif
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <label for="status" class="sr-only">Status</label>
                    <select id="status" name="status"
                            class="w-full rounded-lg border-slate-200 px-3 py-2 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="">All statuses</option>
                        @foreach (\App\Enums\DonationStatus::cases() as $statusOption)
                            <option value="{{ $statusOption->value }}" @selected($filters['status'] === $statusOption->value)>
                                {{ str($statusOption->value)->headline() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="type" class="sr-only">Type</label>
                    <select id="type" name="type"
                            class="w-full rounded-lg border-slate-200 px-3 py-2 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="">All types</option>
                        @foreach (\App\Enums\DonationType::cases() as $typeOption)
                            <option value="{{ $typeOption->value }}" @selected($filters['type'] === $typeOption->value)>
                                {{ str($typeOption->value)->headline() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="date_from" class="sr-only">From date</label>
                    <input type="date" id="date_from" name="date_from" value="{{ $filters['date_from'] }}"
                           placeholder="From"
                           class="w-full rounded-lg border-slate-200 px-3 py-2 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                </div>
                <div>
                    <label for="date_to" class="sr-only">To date</label>
                    <input type="date" id="date_to" name="date_to" value="{{ $filters['date_to'] }}"
                           placeholder="To"
                           class="w-full rounded-lg border-slate-200 px-3 py-2 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                </div>
                <div>
                    <label for="amount_min" class="sr-only">Minimum amount</label>
                    <input type="number" id="amount_min" name="amount_min" value="{{ $filters['amount_min'] }}"
                           min="0" step="0.01" placeholder="Min amount"
                           class="w-full rounded-lg border-slate-200 px-3 py-2 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                </div>
                <div>
                    <label for="amount_max" class="sr-only">Maximum amount</label>
                    <input type="number" id="amount_max" name="amount_max" value="{{ $filters['amount_max'] }}"
                           min="0" step="0.01" placeholder="Max amount"
                           class="w-full rounded-lg border-slate-200 px-3 py-2 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                </div>
            </div>
            <div class="mt-3 flex items-center gap-3">
                <button type="submit"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-slate-900 px-4 py-2 text-xs font-bold text-white transition hover:bg-slate-800">
                    <x-heroicon name="list-bullet" class="h-3.5 w-3.5" />
                    Filter
                </button>
                <a href="{{ $subscription !== null ? route('donorportal.donations', ['organization' => $organization, 'subscription' => $subscription->public_id]) : route('donorportal.donations', $organization) }}"
                   class="text-xs font-semibold text-slate-500 transition hover:text-slate-700">
                    Reset
                </a>
            </div>
        </form>

        <div class="rounded-xl bg-white overflow-hidden donor-card">
            @forelse ($donations as $donation)
                @php
                    $statusClass = match ($donation->status) {
                        \App\Enums\DonationStatus::Succeeded => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                        \App\Enums\DonationStatus::Pending   => 'bg-amber-50 text-amber-700 border-amber-200',
                        \App\Enums\DonationStatus::Failed    => 'bg-red-50 text-red-600 border-red-200',
                        \App\Enums\DonationStatus::Refunded  => 'bg-slate-50 text-slate-600 border-slate-200',
                    };
                    $statusLabel = ($donation->status === \App\Enums\DonationStatus::Succeeded ? '✓ ' : '')
                        . str($donation->status->value)->headline();
                    $typeClass = $donation->type === \App\Enums\DonationType::Recurring
                        ? 'bg-blue-50 text-blue-700 border-blue-200'
                        : 'bg-amber-50 text-amber-700 border-amber-200';
                    $typeLabel = $donation->type === \App\Enums\DonationType::Recurring ? 'Recurring' : 'One-time';
                    $receiptUrl = route('donorportal.donations.receipt.download', ['organization' => $organization, 'donation' => $donation]);
                    $detailUrl = route('donorportal.donations.detail', ['organization' => $organization, 'donation' => $donation]);
                @endphp

                {{-- Mobile card --}}
                <div class="sm:hidden p-4 {{ ! $loop->last ? 'border-b border-slate-100' : '' }}">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-bold text-slate-900 truncate">{{ $donation->campaign->title }}</p>
                            <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                                <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide {{ $statusClass }}">
                                    {{ $statusLabel }}
                                </span>
                                <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide {{ $typeClass }}">
                                    {{ $typeLabel }}
                                </span>
                            </div>
                        </div>
                        <div class="flex-shrink-0 text-right">
                            <p class="text-base font-black text-slate-900">{{ $donation->formatted_amount }}</p>
                        </div>
                    </div>
                    <div class="mt-3 grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Date</p>
                            <p class="mt-0.5 font-semibold text-slate-700">{{ myrTime($donation->created_at, withLabel: false, format: 'd M Y') }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Payment method</p>
                            <div class="mt-0.5 flex items-center gap-2">
                                <x-dynamic-component :component="$donation->card_icon_component" class="h-5 w-auto flex-shrink-0" />
                                <span class="font-medium text-slate-700 truncate">{{ $donation->payment_method_display }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3 flex items-center gap-2">
                        @if ($donation->status === \App\Enums\DonationStatus::Succeeded)
                            <a href="{{ $receiptUrl }}"
                               @click.stop
                               class="flex-1 inline-flex items-center justify-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-bold text-slate-700 transition hover:bg-slate-50 hover:text-slate-900">
                                <x-heroicon name="arrow-down-tray" class="h-4 w-4" />
                                Receipt
                            </a>
                        @endif
                        <button type="button"
                                @click="openDetail('{{ $detailUrl }}')"
                                class="flex-1 inline-flex items-center justify-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-bold text-slate-700 transition hover:bg-slate-50 hover:text-slate-900">
                            Details
                        </button>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center sm:p-16">
                    <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-slate-100">
                        <x-heroicon name="gift" class="h-7 w-7 text-slate-400" />
                    </div>
                    <p class="text-sm font-bold text-slate-700">No donations found</p>
                    <p class="mt-1.5 text-xs text-slate-500">Try adjusting your filters or make a donation to get started.</p>
                </div>
            @endforelse
        </div>

        {{-- Desktop table --}}
        <div class="hidden sm:block rounded-xl bg-white overflow-hidden donor-card">
            @if ($donations->isNotEmpty())
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="bg-slate-50">
                        <tr>
                            <th scope="col" class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Amount</th>
                            <th scope="col" class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Date</th>
                            <th scope="col" class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Payment method</th>
                            <th scope="col" class="px-5 py-3 text-right text-xs font-bold uppercase tracking-wide text-slate-500">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($donations as $donation)
                            @php
                                $statusClass = match ($donation->status) {
                                    \App\Enums\DonationStatus::Succeeded => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    \App\Enums\DonationStatus::Pending   => 'bg-amber-50 text-amber-700 border-amber-200',
                                    \App\Enums\DonationStatus::Failed    => 'bg-red-50 text-red-600 border-red-200',
                                    \App\Enums\DonationStatus::Refunded  => 'bg-slate-50 text-slate-600 border-slate-200',
                                };
                                $statusLabel = ($donation->status === \App\Enums\DonationStatus::Succeeded ? '✓ ' : '')
                                    . str($donation->status->value)->headline();
                                $typeClass = $donation->type === \App\Enums\DonationType::Recurring
                                    ? 'bg-blue-50 text-blue-700 border-blue-200'
                                    : 'bg-amber-50 text-amber-700 border-amber-200';
                                $typeLabel = $donation->type === \App\Enums\DonationType::Recurring ? 'Recurring' : 'One-time';
                                $receiptUrl = route('donorportal.donations.receipt.download', ['organization' => $organization, 'donation' => $donation]);
                                $detailUrl = route('donorportal.donations.detail', ['organization' => $organization, 'donation' => $donation]);
                            @endphp
                            <tr class="transition hover:bg-slate-50/60">
                                <td class="px-5 py-4">
                                    <p class="text-sm font-black text-slate-900">{{ $donation->formatted_amount }}</p>
                                    <p class="mt-0.5 text-xs text-slate-500 truncate max-w-[16rem]">{{ $donation->campaign->title }}</p>
                                    <div class="mt-2 flex items-center gap-1.5">
                                        <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide {{ $statusClass }}">
                                            {{ $statusLabel }}
                                        </span>
                                        <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide {{ $typeClass }}">
                                            {{ $typeLabel }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <p class="text-sm font-semibold text-slate-700">{{ myrTime($donation->created_at, withLabel: false, format: 'd M Y') }}</p>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <x-dynamic-component :component="$donation->card_icon_component" class="h-6 w-auto flex-shrink-0" />
                                        <span class="text-sm font-medium text-slate-700">{{ $donation->payment_method_display }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button type="button"
                                                @click="openDetail('{{ $detailUrl }}')"
                                                class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-600 transition hover:bg-slate-50 hover:text-slate-900">
                                            Details
                                        </button>
                                        @if ($donation->status === \App\Enums\DonationStatus::Succeeded)
                                            <a href="{{ $receiptUrl }}"
                                               @click.stop
                                               class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-600 transition hover:bg-slate-50 hover:text-slate-900">
                                                <x-heroicon name="arrow-down-tray" class="h-3.5 w-3.5" />
                                                Receipt
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="px-8 py-16 text-center">
                    <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-slate-100">
                        <x-heroicon name="gift" class="h-7 w-7 text-slate-400" />
                    </div>
                    <p class="text-sm font-bold text-slate-700">No donations found</p>
                    <p class="mt-1.5 text-xs text-slate-500">Try adjusting your filters or make a donation to get started.</p>
                </div>
            @endif
        </div>

        @if ($donations->hasPages())
            <div class="mt-8">{{ $donations->links() }}</div>
        @endif

        {{-- Detail slide-over --}}
        <div x-show="detailOpen"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             x-cloak
             class="fixed inset-0 z-[60] bg-black/40"
             aria-hidden="true"
             @click="closeDetail">
        </div>
        <div x-show="detailOpen"
             x-transition:enter="transform transition ease-out duration-300"
             x-transition:enter-start="translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transform transition ease-in duration-200"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="translate-x-full"
             x-cloak
             class="fixed inset-y-0 right-0 z-[60] w-full max-w-md bg-white shadow-2xl"
             role="dialog"
             aria-modal="true"
             tabindex="-1"
             @keydown.escape.window="closeDetail">
            <div class="flex h-full flex-col">
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                    <h2 class="text-base font-black text-slate-900">Donation details</h2>
                    <button type="button"
                            @click="closeDetail"
                            class="flex h-8 w-8 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
                            aria-label="Close details">
                        <x-heroicon name="x-mark" class="h-5 w-5" />
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto">
                    <div x-show="detailLoading" class="space-y-4 p-6">
                        <div class="h-6 w-1/3 animate-pulse rounded bg-slate-100"></div>
                        <div class="h-24 animate-pulse rounded-xl bg-slate-100"></div>
                        <div class="h-32 animate-pulse rounded-xl bg-slate-100"></div>
                        <div class="h-20 animate-pulse rounded-xl bg-slate-100"></div>
                    </div>
                    <div x-show="! detailLoading" x-html="detailHtml"></div>
                </div>
            </div>
        </div>
    </div>
</x-donor-skeleton>
@endsection
