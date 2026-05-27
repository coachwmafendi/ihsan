@extends('donor.layout')

@section('title', 'Donations')

@section('content')
<div class="animate-content">
        <div class="mb-8 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-black tracking-tight text-slate-900 [letter-spacing:-0.02em]">Donations</h1>
                @if ($subscription !== null)
                    <p class="mt-0.5 text-xs text-slate-500">
                        Payment history for <strong>{{ $subscription->campaign->title }}</strong>
                        · {{ $subscription->currency_symbol }} {{ number_format($subscription->amount, 2) }}/{{ $subscription->interval->value }}
                    </p>
                    <a href="{{ route('donorportal.donations') }}"
                       class="mt-2 inline-flex items-center gap-1 text-xs font-semibold text-emerald-600 hover:text-emerald-700">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
                        </svg>
                        Back to all donations
                    </a>
                @else
                    <p class="mt-0.5 text-xs text-slate-500">Your complete giving history.</p>
                @endif
            </div>
            @if ($donationCount > 0 && $subscription === null)
                <a href="{{ route('donorportal.receipts.download-all') }}"
                   class="inline-flex items-center gap-1.5 rounded-full border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-600 shadow-sm transition hover:border-slate-400 hover:bg-slate-50 hover:text-slate-900">
                    <svg class="h-4 w-4 text-rose-500" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    Download all receipts
                </a>
            @endif
        </div>

        <div class="mb-6 grid grid-cols-2 gap-3">
            <div class="rounded-xl bg-white p-4" style="border:1.5px solid #e2e8f0;">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Total Given</p>
                @if (count($currencyBreakdown) > 1)
                    <p class="mt-1.5 text-xl font-black text-emerald-700">{{ implode(' + ', $currencyBreakdown) }}</p>
                    <p class="mt-1 text-[10px] text-slate-400">≈ MYR {{ number_format($totalGiven, 2) }}</p>
                @else
                    <p class="mt-1.5 text-xl font-black text-emerald-700">{{ reset($currencyBreakdown) ?? 'RM 0.00' }}</p>
                    @if (count($currencyBreakdown) === 1 && array_key_first($currencyBreakdown) !== 'myr')
                        <p class="mt-1 text-[10px] text-slate-400">≈ MYR {{ number_format($totalGiven, 2) }}</p>
                    @endif
                @endif
            </div>
            <div class="rounded-xl bg-white p-4" style="border:1.5px solid #e2e8f0;">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Donations</p>
                <p class="mt-1.5 text-xl font-black text-slate-900">{{ $donationCount }}</p>
            </div>
        </div>

        <div class="space-y-3">
            @forelse ($donations as $donation)
                <div class="rounded-xl bg-white p-4 transition hover:shadow-md" style="border:1.5px solid #e2e8f0;">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-bold text-slate-900">{{ $donation->campaign->title }}</p>
                            <p class="mt-0.5 text-xs text-slate-500">{{ $donation->campaign->organization->name }}</p>
                        </div>
                        <div class="flex-shrink-0 text-right">
                            <p class="text-base font-black text-slate-900">{{ $donation->formatted_amount }}</p>
                            <p class="mt-0.5 text-[11px] text-slate-400">{{ $donation->created_at->format('d M Y') }}</p>
                        </div>
                    </div>
                    <div class="mt-3 flex flex-wrap items-center gap-2">
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
                        @endphp
                        <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-[11px] font-bold {{ $statusClass }}">
                            {{ $statusLabel }}
                        </span>
                        <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-[11px] font-bold {{ $typeClass }}">
                            {{ $typeLabel }}
                        </span>
                        @if ($donation->status === \App\Enums\DonationStatus::Succeeded)
                            <a href="{{ route('donorportal.donations.receipt.download', $donation) }}"
                               class="ml-auto inline-flex items-center gap-1 rounded-full border border-slate-200 bg-white px-2.5 py-0.5 text-[11px] font-bold text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                </svg>
                                Receipt
                            </a>
                        @endif
                    </div>
                </div>
            @empty
                <div class="rounded-xl bg-white px-8 py-16 text-center" style="border:1.5px solid #e2e8f0;">
                    <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-slate-100">
                        <svg class="h-7 w-7 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                        </svg>
                    </div>
                    <p class="text-sm font-bold text-slate-700">No donations yet</p>
                    <p class="mt-1.5 text-xs text-slate-500">Your giving history will appear here once you make a donation.</p>
                </div>
            @endforelse
        </div>

        @if ($donations->hasPages())
            <div class="mt-8">{{ $donations->links() }}</div>
        @endif
    </div>
@endsection
