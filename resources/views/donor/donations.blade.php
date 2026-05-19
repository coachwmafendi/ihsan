@extends('donor.layout')

@section('title', 'Donation History')

@section('content')
    <h1 class="text-2xl font-semibold text-slate-900">Donation History</h1>
    <p class="mt-1 text-sm text-slate-500">Hi {{ $donor->name }}, here are your donations.</p>

    <div class="mt-6 space-y-3">
        @forelse ($donations as $donation)
            <div class="rounded-lg border border-slate-200 bg-white p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-medium text-slate-900">{{ $donation->campaign->title }}</p>
                        <p class="text-sm text-slate-500">{{ $donation->campaign->organization->name }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-lg font-semibold text-slate-900">RM {{ number_format($donation->gross_amount, 2) }}</p>
                        <p class="text-xs text-slate-400">{{ $donation->created_at->format('d M Y') }}</p>
                    </div>
                </div>
                <div class="mt-2">
                    <span class="inline-block rounded-full px-2 py-0.5 text-xs font-medium
                        {{ $donation->status === \App\Enums\DonationStatus::Succeeded ? 'bg-emerald-100 text-emerald-700' : '' }}
                        {{ $donation->status === \App\Enums\DonationStatus::Pending ? 'bg-amber-100 text-amber-700' : '' }}
                        {{ $donation->status === \App\Enums\DonationStatus::Failed ? 'bg-red-100 text-red-700' : '' }}
                        {{ $donation->status === \App\Enums\DonationStatus::Refunded ? 'bg-slate-100 text-slate-600' : '' }}">
                        {{ $donation->status->value }}
                    </span>
                    <span class="ml-2 inline-block rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700">
                        {{ $donation->type === \App\Enums\DonationType::Recurring ? 'Recurring' : 'One-time' }}
                    </span>
                </div>
            </div>
        @empty
            <div class="rounded-lg border border-slate-200 bg-white p-8 text-center text-slate-500">
                No donations yet.
            </div>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $donations->links() }}
    </div>
@endsection
