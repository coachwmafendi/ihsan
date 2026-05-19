@extends('donor.layout')

@section('title', 'My Subscriptions')

@section('content')
    <h1 class="text-2xl font-semibold text-slate-900">My Subscriptions</h1>
    <p class="mt-1 text-sm text-slate-500">Manage your recurring donations.</p>

    <div class="mt-6 space-y-3">
        @forelse ($subscriptions as $subscription)
            <div class="rounded-lg border border-slate-200 bg-white p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-medium text-slate-900">{{ $subscription->campaign->title }}</p>
                        <p class="text-sm text-slate-500">{{ $subscription->campaign->organization->name }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-lg font-semibold text-slate-900">RM {{ number_format($subscription->amount, 2) }}/{{ $subscription->interval->value }}</p>
                        <p class="text-xs text-slate-400">
                            @if ($subscription->current_period_end)
                                Next: {{ $subscription->current_period_end->format('d M Y') }}
                            @endif
                        </p>
                    </div>
                </div>
                <div class="mt-3 flex items-center justify-between">
                    <span class="inline-block rounded-full px-2 py-0.5 text-xs font-medium
                        {{ $subscription->status === \App\Enums\SubscriptionStatus::Active ? 'bg-emerald-100 text-emerald-700' : '' }}
                        {{ $subscription->status === \App\Enums\SubscriptionStatus::Cancelled ? 'bg-slate-100 text-slate-600' : '' }}
                        {{ $subscription->status === \App\Enums\SubscriptionStatus::PastDue ? 'bg-red-100 text-red-700' : '' }}
                        {{ $subscription->status === \App\Enums\SubscriptionStatus::Paused ? 'bg-amber-100 text-amber-700' : '' }}">
                        {{ $subscription->status->value }}
                    </span>

                    @if ($subscription->status === \App\Enums\SubscriptionStatus::Active)
                        <form action="{{ route('donor.subscriptions.cancel', $subscription) }}" method="POST" onsubmit="return confirm('Are you sure you want to cancel this subscription?')">
                            @csrf
                            <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-700">Cancel</button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <div class="rounded-lg border border-slate-200 bg-white p-8 text-center text-slate-500">
                No subscriptions yet.
            </div>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $subscriptions->links() }}
    </div>
@endsection
