@extends('donor.layout')

@section('title', 'Dashboard')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-semibold tracking-tight text-stone-900">Dashboard</h1>
        <p class="mt-1 text-sm font-medium text-stone-600">Hi {{ $donor->name }}, here's your giving overview.</p>
    </div>

    <div class="mb-8 grid grid-cols-3 gap-4">
        <div class="rounded-xl border border-stone-200/70 bg-white p-6">
            <p class="text-xs font-medium uppercase tracking-wider text-stone-400">Total Given</p>
            <p class="mt-2 text-3xl font-bold text-emerald-700">RM {{ number_format($totalGiven, 2) }}</p>
        </div>
        <div class="rounded-xl border border-stone-200/70 bg-white p-6">
            <p class="text-xs font-medium uppercase tracking-wider text-stone-400">Active Subscriptions</p>
            <p class="mt-2 text-3xl font-bold text-stone-900">{{ $activeSubscriptions }}</p>
        </div>
        <div class="rounded-xl border border-stone-200/70 bg-white p-6">
            <p class="text-xs font-medium uppercase tracking-wider text-stone-400">Monthly Recurring</p>
            <p class="mt-2 text-3xl font-bold text-emerald-700">
                RM {{ number_format($monthlyRecurring, 2) }}
                <span class="text-base font-medium text-stone-400">/mo</span>
            </p>
        </div>
    </div>

    <div class="mb-8 rounded-xl border border-stone-200/70 bg-white p-6">
        <h2 class="mb-4 text-base font-semibold text-stone-900">Monthly Giving</h2>
        <canvas id="monthlyChart" height="100"></canvas>
    </div>

    <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
        <div class="rounded-xl border border-stone-200/70 bg-white p-6">
            <h2 class="mb-4 text-base font-semibold text-stone-900">By Campaign</h2>
            @if ($campaignBreakdown->isNotEmpty())
                <div class="space-y-3">
                    @foreach ($campaignBreakdown as $item)
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                                <p class="text-sm font-medium text-stone-700">{{ $item->campaign }}</p>
                            </div>
                            <p class="text-sm font-semibold text-stone-900">RM {{ number_format($item->total, 2) }}</p>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-stone-500">No donations yet.</p>
            @endif
        </div>

        <div class="rounded-xl border border-stone-200/70 bg-white p-6">
            <h2 class="mb-4 text-base font-semibold text-stone-900">Recent Activity</h2>
            @if ($recentDonations->isNotEmpty())
                <div class="space-y-4">
                    @foreach ($recentDonations as $donation)
                        <div class="flex items-center justify-between">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-stone-900">{{ $donation->campaign->title }}</p>
                                <p class="text-xs text-stone-400">{{ $donation->created_at->diffForHumans() }}</p>
                            </div>
                            <p class="flex-shrink-0 text-sm font-semibold text-stone-900">RM {{ number_format($donation->gross_amount, 2) }}</p>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-stone-500">No activity yet.</p>
            @endif
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
                    label: 'Giving',
                    data: monthlyData.map(d => d.total),
                    backgroundColor: '#10b981',
                    borderRadius: 6,
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: (value) => 'RM ' + value.toLocaleString(),
                        },
                        grid: { color: '#e5e5e5' },
                    },
                    x: {
                        grid: { display: false },
                    }
                }
            }
        });
    </script>
@endpush
