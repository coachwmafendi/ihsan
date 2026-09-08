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
        <div class="mb-6 grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="h-24 animate-pulse rounded-xl bg-slate-100 donor-card-skeleton"></div>
            <div class="h-24 animate-pulse rounded-xl bg-slate-100 donor-card-skeleton"></div>
            <div class="h-24 animate-pulse rounded-xl bg-slate-100 donor-card-skeleton"></div>
        </div>
        <div class="mb-6 h-32 animate-pulse rounded-xl bg-slate-100 donor-card-skeleton"></div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="h-64 animate-pulse rounded-xl bg-slate-100 donor-card-skeleton"></div>
            <div class="h-64 animate-pulse rounded-xl bg-slate-100 donor-card-skeleton"></div>
        </div>
    </x-slot:skeleton>
    <div x-data="{
        donationModalOpen: false,
        donationModalUrl: @js($donationModalUrl),
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

            @php
                $isAdminImpersonating = auth()->user()?->role === App\Enums\UserRole::NgoAdmin
                    && session()->has('admin_impersonating_donor_id');
            @endphp

            @if ($isAdminImpersonating)
                <x-ui.tooltip text="This feature is available to supporters only." position="top">
                    <button type="button" disabled
                            class="mt-6 inline-flex items-center gap-2.5 rounded-full border border-slate-300 bg-white px-6 py-3 text-sm font-semibold text-slate-700 shadow-sm opacity-60 cursor-not-allowed">
                        <x-heroicon name="heart" class="h-5 w-5 text-rose-500" />
                        Make a new donation
                    </button>
                </x-ui.tooltip>
            @else
                <a href="{{ $donationModalUrl }}"
                   @click.prevent="donationModalOpen = true"
                   class="mt-6 inline-flex items-center gap-2.5 rounded-full border border-slate-300 bg-white px-6 py-3 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-400 hover:bg-slate-50 hover:text-slate-900">
                    <x-heroicon name="heart" class="h-5 w-5 text-rose-500" />
                    Make a new donation
                </a>
            @endif

            <div x-show="donationModalOpen"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 x-cloak
                 class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/50 p-3 sm:p-6"
                 role="dialog"
                 aria-modal="true"
                 @click.self="donationModalOpen = false"
                 @keydown.escape.window="donationModalOpen = false">
                 {{--
                     The height is in dvh, not vh: on a phone, vh measures the
                     viewport with the browser's own bars hidden, so a panel
                     sized that way hangs off the top of the screen and takes
                     the close button with it.
                 --}}
                 <div class="relative flex h-[92dvh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
                    {{--
                        The button floats over the frame rather than sitting in
                        a bar above it: nothing can push it out of view, and the
                        modal stops wearing a second empty header on top of the
                        checkout's own.
                    --}}
                    <button type="button"
                            class="absolute right-3 top-3 z-10 flex size-9 items-center justify-center rounded-full bg-white text-slate-500 shadow-md ring-1 ring-slate-900/10 transition hover:bg-slate-100 hover:text-slate-900"
                            aria-label="Close donation form"
                            @click="donationModalOpen = false">
                        <x-heroicon name="x-mark" class="size-5" />
                    </button>
                    <iframe :src="donationModalOpen ? donationModalUrl : 'about:blank'"
                            title="Donation form"
                            allow="{{ $donationModalIframeAllow }}"
                            class="h-full w-full flex-1 border-0"></iframe>
                </div>
            </div>
        </div>

        <div class="mb-6 grid grid-cols-1 sm:grid-cols-3 gap-3">
            <a href="{{ route('donorportal.donations', $organization) }}" wire:navigate
               class="block rounded-xl bg-white p-4 transition hover:bg-slate-50 donor-card">
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
            </a>
            @if ($activeSubscriptions > 0)
                <a href="{{ route('donorportal.subscriptions', $organization) }}" wire:navigate
                   class="block rounded-xl bg-white p-4 transition hover:bg-slate-50 donor-card">
            @else
                <div class="rounded-xl bg-white p-4 donor-card">
            @endif
                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Active Plans</p>
                <p class="mt-1.5 text-xl font-black text-slate-900">{{ $activeSubscriptions }}</p>
            @if ($activeSubscriptions > 0)
                </a>
            @else
                </div>
            @endif
            <div class="rounded-xl bg-white p-4 donor-card">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Monthly</p>
                @if (count($monthlyRecurringFormatted) > 1)
                    <p class="mt-1.5 text-xl font-black text-emerald-700">{{ implode(' + ', $monthlyRecurringFormatted) }}</p>
                @else
                    <p class="mt-1.5 text-xl font-black text-emerald-700">{{ reset($monthlyRecurringFormatted) ?? 'MYR 0.00' }}</p>
                @endif
            </div>
        </div>

        <div class="mb-6 rounded-xl bg-white p-5 donor-card">
            <h2 class="mb-4 text-sm font-bold text-slate-900">Giving by Campaign</h2>
            <div class="flex flex-col sm:flex-row items-center gap-6">
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
                                <p class="shrink-0 text-xs font-black text-slate-900">MYR {{ number_format((float) $item->total, 2) }}</p>
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
                                <p class="text-xs text-slate-400">{{ $donation->created_at->diffForHumans() }}</p>
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
