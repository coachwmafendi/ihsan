@extends('donor.layout')

@section('title', 'Subscriptions')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-semibold tracking-tight text-stone-900">Subscriptions</h1>
        <p class="mt-1 text-sm text-stone-500">Manage your recurring donations.</p>
    </div>

    <div class="space-y-4">
        @forelse ($subscriptions as $subscription)
            <div class="rounded-xl border border-stone-200/60 bg-white p-6 shadow-sm transition hover:shadow-md">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0 flex-1">
                        <p class="font-medium text-stone-900">{{ $subscription->campaign->title }}</p>
                        <p class="mt-1 text-sm text-stone-500">{{ $subscription->campaign->organization->name }}</p>
                    </div>
                    <div class="flex-shrink-0 text-right">
                        <p class="text-xl font-bold text-stone-900">RM {{ number_format($subscription->amount, 2) }}<span class="text-sm font-normal text-stone-400">/{{ $subscription->interval->value }}</span></p>
                        @if ($subscription->current_period_end)
                            <p class="mt-1 text-xs text-stone-400">Next: {{ $subscription->current_period_end->format('d M Y') }}</p>
                        @endif
                    </div>
                </div>
                <div class="mt-4 flex items-center justify-between">
                    <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium
                        {{ $subscription->status === \App\Enums\SubscriptionStatus::Active ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' : '' }}
                        {{ $subscription->status === \App\Enums\SubscriptionStatus::Cancelled ? 'bg-stone-50 text-stone-600 ring-1 ring-stone-200' : '' }}
                        {{ $subscription->status === \App\Enums\SubscriptionStatus::PastDue ? 'bg-red-50 text-red-600 ring-1 ring-red-200' : '' }}
                        {{ $subscription->status === \App\Enums\SubscriptionStatus::Paused ? 'bg-amber-50 text-amber-700 ring-1 ring-amber-200' : '' }}">
                        @if ($subscription->status === \App\Enums\SubscriptionStatus::Active)
                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                        @endif
                        {{ str($subscription->status->value)->headline() }}
                    </span>

                    @if ($subscription->status === \App\Enums\SubscriptionStatus::Active)
                        <form action="{{ route('donorportal.subscriptions.cancel', $subscription) }}" method="POST" onsubmit="return confirm('Are you sure you want to cancel this subscription?')">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-1 rounded-lg px-3 py-1.5 text-sm font-medium text-red-600 transition hover:bg-red-50 hover:text-red-700">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                                Cancel
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <div class="rounded-xl border border-stone-200/60 bg-white px-8 py-16 text-center">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-stone-100">
                    <svg class="h-7 w-7 text-stone-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 5.25h16.5m-16.5 4.5h16.5m-16.5 4.5h16.5m-16.5 4.5h16.5" />
                    </svg>
                </div>
                <p class="text-base font-medium text-stone-700">No subscriptions yet</p>
                <p class="mt-2 text-sm text-stone-500">Set up a recurring donation to start a subscription.</p>
            </div>
        @endforelse
    </div>

    @if ($subscriptions->hasPages())
        <div class="mt-8">
            {{ $subscriptions->links() }}
        </div>
    @endif
@endsection
