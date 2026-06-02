@extends('donor.layout')

@section('title', 'Dashboard')

@section('content')
<x-donor-skeleton>
    <x-slot:skeleton>
        <div class="mb-8">
            <div class="h-8 w-64 animate-pulse rounded-lg bg-slate-200"></div>
            <div class="mt-2 h-4 w-80 animate-pulse rounded bg-slate-100"></div>
            <div class="mt-6 h-10 w-48 animate-pulse rounded-full bg-slate-200"></div>
        </div>
        <div class="mb-6 grid grid-cols-3 gap-3">
            <div class="h-24 animate-pulse rounded-xl bg-slate-100 donor-card-skeleton"></div>
            <div class="h-24 animate-pulse rounded-xl bg-slate-100 donor-card-skeleton"></div>
            <div class="h-24 animate-pulse rounded-xl bg-slate-100 donor-card-skeleton"></div>
        </div>
        <div class="mb-6 h-32 animate-pulse rounded-xl bg-slate-100 donor-card-skeleton"></div>
        <div class="grid grid-cols-2 gap-4">
            <div class="h-64 animate-pulse rounded-xl bg-slate-100 donor-card-skeleton"></div>
            <div class="h-64 animate-pulse rounded-xl bg-slate-100 donor-card-skeleton"></div>
        </div>
    </x-slot:skeleton>
    <div x-data="{
        donationModalOpen: false,
        donationModalDesktopUrl: @js($donationModalDesktopUrl),
        donationModalMobileUrl: @js($donationModalMobileUrl),
        donationModalUrl() {
            return window.matchMedia('(min-width: 768px)').matches
                ? this.donationModalDesktopUrl
                : this.donationModalMobileUrl;
        },
    }"
         @message.window="if ($event.data && $event.data.type === 'donation-popup-close') { donationModalOpen = false; window.location.reload(); }">
        <div class="mb-8">
            <h1 class="text-2xl font-black tracking-tight text-slate-900 [letter-spacing:-0.02em]">Hi, {{ $donor->name }} <span class="ml-1">👋</span></h1>
            <p class="mt-1 text-sm font-bold text-slate-600">
                Welcome to the <span class="uppercase">{{ $organization->name }}</span> Donor Portal
            </p>
            @if (filled($organization->settings['portal_tagline'] ?? null))
                <p class="mt-1 text-sm text-slate-500">{{ $organization->settings['portal_tagline'] }}</p>
            @endif

            <a href="{{ $donationModalUrl }}"
               @click.prevent="donationModalOpen = true"
               class="mt-6 inline-flex items-center gap-2.5 rounded-full border border-slate-300 bg-white px-6 py-3 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-400 hover:bg-slate-50 hover:text-slate-900">
                <x-heroicon name="heart" class="h-5 w-5 text-rose-500" />
                Make a new donation
            </a>

            <div x-show="donationModalOpen"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 x-cloak
                 class="fixed inset-0 z-[9999] flex bg-black/40 px-4 py-4"
                 role="dialog"
                 aria-modal="true"
                 @click.self="donationModalOpen = false"
                 @keydown.escape.window="donationModalOpen = false">
                <div class="relative mx-auto flex w-full max-w-6xl flex-col overflow-hidden rounded-2xl bg-white/80 backdrop-blur-xl shadow-2xl">
                    <div class="flex items-center justify-end border-b border-slate-100 px-4 py-2">
                        <button type="button"
                                class="flex h-8 w-8 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
                                aria-label="Close donation form"
                                @click="donationModalOpen = false">
                            <x-heroicon name="x-mark" class="h-5 w-5" />
                        </button>
                    </div>
                    <iframe :src="donationModalOpen ? donationModalUrl() : 'about:blank'"
                            title="Donation form"
                            class="h-full w-full flex-1 border-0 min-h-[70vh]"
                            allow="payment *"></iframe>
                </div>
            </div>
        </div>

        <div class="mb-6 grid grid-cols-3 gap-3">
            <a href="{{ route('donorportal.donations', $organization) }}" wire:navigate
               class="block rounded-xl bg-white p-4 transition hover:bg-slate-50 donor-card">
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
            </a>
            @if ($activeSubscriptions > 0)
                <a href="{{ route('donorportal.subscriptions', $organization) }}" wire:navigate
                   class="block rounded-xl bg-white p-4 transition hover:bg-slate-50 donor-card">
            @else
                <div class="rounded-xl bg-white p-4 donor-card">
            @endif
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Active Plans</p>
                <p class="mt-1.5 text-xl font-black text-slate-900">{{ $activeSubscriptions }}</p>
            @if ($activeSubscriptions > 0)
                </a>
            @else
                </div>
            @endif
            <div class="rounded-xl bg-white p-4 donor-card">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Monthly</p>
                @if (count($monthlyRecurringFormatted) > 1)
                    <p class="mt-1.5 text-xl font-black text-emerald-700">{{ implode(' + ', $monthlyRecurringFormatted) }}</p>
                @else
                    <p class="mt-1.5 text-xl font-black text-emerald-700">{{ reset($monthlyRecurringFormatted) ?? 'RM 0.00' }}</p>
                @endif
            </div>
        </div>

        <div class="mb-6 rounded-xl bg-white p-5 donor-card">
            <h2 class="mb-4 text-sm font-bold text-slate-900">Giving by Campaign</h2>
            <div class="flex items-center gap-6">
                <div class="mx-auto w-48 shrink-0">
                    <canvas id="campaignDonut"></canvas>
                </div>
                <div class="min-w-0 flex-1 space-y-2">
                    @if ($campaignChartData->isNotEmpty())
                        @php
                            $donutColors = ['#10b981', '#0ea5e9', '#8b5cf6', '#f59e0b', '#ef4444', '#06b6d4', '#84cc16', '#d946ef'];
                        @endphp
                        @foreach ($campaignChartData as $i => $item)
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-2 min-w-0">
                                    <span class="h-2.5 w-2.5 shrink-0 rounded-full" style="background:{{ $donutColors[$i % count($donutColors)] }};"></span>
                                    <p class="truncate text-xs font-semibold text-slate-700">{{ $item->campaign }}</p>
                                </div>
                                <p class="shrink-0 text-xs font-black text-slate-900">RM {{ number_format((float) $item->total, 2) }}</p>
                            </div>
                        @endforeach
                    @else
                        <p class="text-xs text-slate-400">No donations yet.</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="rounded-xl bg-white p-5 donor-card">
            <h2 class="mb-4 text-sm font-bold text-slate-900">Recent Activity</h2>
            @if ($recentDonations->isNotEmpty())
                <div class="divide-y divide-slate-50">
                    @foreach ($recentDonations as $donation)
                        <div class="flex items-center justify-between py-2 first:pt-0 last:pb-0">
                            <div class="min-w-0 flex-1 pr-3">
                                <p class="truncate text-xs font-bold text-slate-900">{{ $donation->campaign->title }}</p>
                                <p class="text-[11px] text-slate-400">{{ $donation->created_at->diffForHumans() }}</p>
                            </div>
                            <p class="flex-shrink-0 text-xs font-black text-slate-900">
                                {{ $donation->formatted_amount }}
                            </p>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-xs text-slate-400">No activity yet.</p>
            @endif
        </div>
    </div>
</x-donor-skeleton>
@endsection

@push('scripts')
<script>
    function initDonutChart() {
        var canvas = document.getElementById('campaignDonut');
        if (!canvas || typeof Chart === 'undefined') return;
        var existing = Chart.getChart(canvas);
        if (existing) existing.destroy();
        var chartData = @json($campaignChartData);
        var colors = ['#10b981', '#0ea5e9', '#8b5cf6', '#f59e0b', '#ef4444', '#06b6d4', '#84cc16', '#d946ef'];
        new Chart(canvas.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: chartData.map(function(d) { return d.campaign; }),
                datasets: [{
                    data: chartData.map(function(d) { return d.total; }),
                    backgroundColor: colors.slice(0, chartData.length),
                    borderWidth: 0,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                cutout: '65%',
                plugins: {
                    legend: { display: false },
                },
            },
        });
    }
    document.addEventListener('DOMContentLoaded', initDonutChart);
    document.addEventListener('livewire:navigated', initDonutChart);
</script>
@endpush
