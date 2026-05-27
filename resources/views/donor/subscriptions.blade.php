@extends('donor.layout')

@section('title', 'Subscriptions')

@section('content')
<div class="donor-fade" id="donorContent">
<script>document.addEventListener('DOMContentLoaded',()=>{requestAnimationFrame(()=>{requestAnimationFrame(()=>{document.getElementById('donorContent').classList.add('visible')})})})</script>
        <div class="mb-8">
            <h1 class="text-2xl font-black tracking-tight text-slate-900 [letter-spacing:-0.02em]">Subscriptions</h1>
            <p class="mt-0.5 text-xs text-slate-500">Manage your recurring donations.</p>
        </div>

        <div class="space-y-3">
            @forelse ($subscriptions as $subscription)
                <div class="rounded-xl bg-white p-4 transition {{ $subscription->status === \App\Enums\SubscriptionStatus::Cancelled ? 'opacity-60' : 'hover:shadow-md' }}"
                     style="border:1.5px solid #e2e8f0;">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-bold text-slate-900">{{ $subscription->campaign->title }}</p>
                            <p class="mt-0.5 text-xs text-slate-500">{{ $subscription->campaign->organization->name }}</p>
                        </div>
                        <div class="flex-shrink-0 text-right">
                            <p class="text-base font-black text-slate-900">
                                {{ $subscription->currency_symbol }} {{ number_format($subscription->amount, 2) }}<span class="text-xs font-normal text-slate-400">/{{ $subscription->interval->value }}</span>
                            </p>
                            @if ($subscription->current_period_end)
                                <p class="mt-0.5 text-[11px] text-slate-400">
                                    Next: {{ $subscription->current_period_end->format('d M Y') }}
                                </p>
                            @endif
                        </div>
                    </div>
                    <div class="mt-3 flex items-center justify-between">
                        @php
                            $statusClass = match ($subscription->status) {
                                \App\Enums\SubscriptionStatus::Active     => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                \App\Enums\SubscriptionStatus::Cancelled  => 'bg-slate-50 text-slate-600 border-slate-200',
                                \App\Enums\SubscriptionStatus::PastDue    => 'bg-red-50 text-red-600 border-red-200',
                                \App\Enums\SubscriptionStatus::Paused     => 'bg-amber-50 text-amber-700 border-amber-200',
                                \App\Enums\SubscriptionStatus::Incomplete => 'bg-slate-50 text-slate-500 border-slate-200',
                            };
                            $statusPrefix = $subscription->status === \App\Enums\SubscriptionStatus::Active ? '● ' : '';
                        @endphp
                        <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-[11px] font-bold {{ $statusClass }}">
                            {{ $statusPrefix }}{{ str($subscription->status->value)->headline() }}
                        </span>

                        @if ($subscription->status === \App\Enums\SubscriptionStatus::Active)
                            <div class="flex items-center gap-2">
                                <a href="{{ route('donorportal.donations', ['subscription' => $subscription->getKey()]) }}"
                                   class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-600 transition hover:bg-slate-50">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 010 3.75H5.625a1.875 1.875 0 010-3.75z" />
                                    </svg>
                                    History
                                </a>
                                <form action="{{ route('donorportal.subscriptions.cancel', $subscription) }}"
                                      method="POST"
                                      onsubmit="return confirm('Cancel this subscription?')">
                                    @csrf
                                    <button type="submit"
                                            class="inline-flex items-center gap-1 rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-bold text-red-600 transition hover:bg-red-100">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                        </svg>
                                        Cancel
                                    </button>
                                </form>
                            </div>
                        @else
                            <a href="{{ route('donorportal.donations', ['subscription' => $subscription->getKey()]) }}"
                               class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-600 transition hover:bg-slate-50">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 010 3.75H5.625a1.875 1.875 0 010-3.75z" />
                                </svg>
                                History
                            </a>
                        @endif
                    </div>
                </div>
            @empty
                <div class="rounded-xl bg-white px-8 py-16 text-center" style="border:1.5px solid #e2e8f0;">
                    <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-slate-100">
                        <svg class="h-7 w-7 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 5.25h16.5m-16.5 4.5h16.5m-16.5 4.5h16.5m-16.5 4.5h16.5" />
                        </svg>
                    </div>
                    <p class="text-sm font-bold text-slate-700">No subscriptions yet</p>
                    <p class="mt-1.5 text-xs text-slate-500">Set up a recurring donation to start a subscription.</p>
                </div>
            @endforelse
        </div>

        @if ($subscriptions->hasPages())
            <div class="mt-8">{{ $subscriptions->links() }}</div>
        @endif
    </div>
@endsection
