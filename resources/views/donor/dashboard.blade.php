@extends('donor.layout')

@section('title', 'Dashboard')

@section('content')
<div class="animate-content">
        <div class="mb-8">
            <h1 class="text-2xl font-black tracking-tight text-slate-900 [letter-spacing:-0.02em]">Hi, {{ $donor->name }} <span class="ml-1">👋</span></h1>
            @if ($primaryOrganization !== null)
                <p class="mt-1 text-sm font-bold text-slate-600">
                    Welcome to the <span class="uppercase">{{ $primaryOrganization->name }}</span> Donor Portal
                </p>
            @else
                <p class="mt-1 text-sm font-bold text-slate-600">Welcome to the Donor Portal</p>
            @endif

            @php
                $latestCampaign = $recentDonations->first()?->campaign;
                $donationUrl = $latestCampaign !== null
                    ? route('donations.campaign-show', $latestCampaign)
                    : route('home');
            @endphp
            <a href="{{ $donationUrl }}"
               class="mt-6 inline-flex items-center gap-2.5 rounded-full border border-slate-300 bg-white px-6 py-3 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-400 hover:bg-slate-50 hover:text-slate-900">
                <svg class="h-5 w-5 text-rose-500" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M11.645 20.91l-.007-.003-.022-.012a15.247 15.247 0 01-.383-.218 25.18 25.18 0 01-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0112 5.052 5.5 5.5 0 0116.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 01-4.244 3.17 15.247 15.247 0 01-.383.219l-.022.012-.007.004-.003.001a.752.752 0 01-.704 0l-.003-.001z" />
                </svg>
                Make a new donation
            </a>
        </div>

        <div class="mb-6 grid grid-cols-3 gap-3">
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
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Active Plans</p>
                <p class="mt-1.5 text-xl font-black text-slate-900">{{ $activeSubscriptions }}</p>
            </div>
            <div class="rounded-xl bg-white p-4" style="border:1.5px solid #e2e8f0;">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Monthly</p>
                @if (count($monthlyRecurringFormatted) > 1)
                    <p class="mt-1.5 text-xl font-black text-emerald-700">{{ implode(' + ', $monthlyRecurringFormatted) }}</p>
                @else
                    <p class="mt-1.5 text-xl font-black text-emerald-700">{{ reset($monthlyRecurringFormatted) ?? 'RM 0.00' }}</p>
                @endif
            </div>
        </div>

        <div class="mb-6 rounded-xl bg-white p-5" style="border:1.5px solid #e2e8f0;">
            <h2 class="mb-4 text-sm font-bold text-slate-900">Monthly Giving</h2>
            <canvas id="monthlyChart" height="80"></canvas>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div class="rounded-xl bg-white p-5" style="border:1.5px solid #e2e8f0;">
                <h2 class="mb-4 text-sm font-bold text-slate-900">By Campaign</h2>
                @if ($campaignBreakdown->isNotEmpty())
                    @php $dotColors = ['#10b981', '#0ea5e9', '#8b5cf6', '#f59e0b', '#ef4444']; @endphp
                    @php $campaignIndex = 0; @endphp
                    <div class="space-y-3">
                        @foreach ($campaignBreakdown as $campaign => $currencies)
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="h-2 w-2 flex-shrink-0 rounded-full"
                                          style="background:{{ $dotColors[$campaignIndex % count($dotColors)] }};"></span>
                                    <p class="text-xs font-semibold text-slate-700">{{ $campaign }}</p>
                                </div>
                                <p class="text-xs font-black text-slate-900">{{ implode(' + ', $currencies->toArray()) }}</p>
                            </div>
                            @php $campaignIndex++; @endphp
                        @endforeach
                    </div>
                @else
                    <p class="text-xs text-slate-400">No donations yet.</p>
                @endif
            </div>

            <div class="rounded-xl bg-white p-5" style="border:1.5px solid #e2e8f0;">
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
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctx = document.getElementById('monthlyChart').getContext('2d');
        const monthlyData = @json($monthlyDonations);

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: monthlyData.map(d => d.month),
                datasets: [{
                    data: monthlyData.map(d => d.total),
                    backgroundColor: monthlyData.map((d, i) =>
                        i === monthlyData.length - 1 ? '#10b981' : '#f1f5f9'
                    ),
                    borderRadius: 5,
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: v => 'MYR ' + v.toLocaleString(),
                            font: { size: 10 },
                        },
                        grid: { color: '#f1f5f9' },
                        border: { display: false },
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 10 } },
                        border: { display: false },
                    },
                },
            },
        });
    </script>
@endpush
