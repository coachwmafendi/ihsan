@extends('donor.layout')

@section('title', 'Donations')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-semibold tracking-tight text-stone-900">Donations</h1>
        <p class="mt-1 text-sm font-medium text-stone-600">Hi {{ $donor->name }}, here's your giving history. <svg class="inline-block h-4 w-4 text-emerald-600" fill="currentColor" viewBox="0 0 24 24"><path d="M11.645 20.91l-.007-.003-.022-.012a15.247 15.247 0 01-.383-.218 25.18 25.18 0 01-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0112 5.052 5.5 5.5 0 0116.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 01-4.244 3.17 15.247 15.247 0 01-.383.219l-.022.012-.007.004-.003.001a.752.752 0 01-.704 0l-.003-.001z"/></svg></p>
    </div>

    @php
        $totalGiven = $donor->donations->where('status', \App\Enums\DonationStatus::Succeeded)->sum('gross_amount');
        $donationCount = $donor->donations->where('status', \App\Enums\DonationStatus::Succeeded)->count();
    @endphp

    <div class="mb-6 grid grid-cols-2 gap-4">
        <div class="rounded-xl border border-stone-200/70 bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-stone-400">Total Given</p>
            <p class="mt-1 text-2xl font-bold text-emerald-700">RM {{ number_format($totalGiven, 2) }}</p>
        </div>
        <div class="rounded-xl border border-stone-200/70 bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-stone-400">Donations</p>
            <p class="mt-1 text-2xl font-bold text-stone-900">{{ $donationCount }}</p>
        </div>
    </div>

    <div class="space-y-3">
        @forelse ($donations as $donation)
            <div class="rounded-xl border border-stone-200/60 bg-white p-5 shadow-sm transition hover:shadow-md">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0 flex-1">
                        <p class="font-medium text-stone-900">{{ $donation->campaign->title }}</p>
                        <p class="mt-0.5 text-sm text-stone-500">{{ $donation->campaign->organization->name }}</p>
                    </div>
                    <div class="flex-shrink-0 text-right">
                        <p class="text-xl font-bold text-stone-900">RM {{ number_format($donation->gross_amount, 2) }}</p>
                        <p class="mt-0.5 text-xs text-stone-400">{{ $donation->created_at->format('d M Y') }}</p>
                    </div>
                </div>
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium
                        {{ $donation->status === \App\Enums\DonationStatus::Succeeded ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' : '' }}
                        {{ $donation->status === \App\Enums\DonationStatus::Pending ? 'bg-amber-50 text-amber-700 ring-1 ring-amber-200' : '' }}
                        {{ $donation->status === \App\Enums\DonationStatus::Failed ? 'bg-red-50 text-red-600 ring-1 ring-red-200' : '' }}
                        {{ $donation->status === \App\Enums\DonationStatus::Refunded ? 'bg-stone-50 text-stone-600 ring-1 ring-stone-200' : '' }}">
                        @if ($donation->status === \App\Enums\DonationStatus::Succeeded)
                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                        @endif
                        {{ $donation->status->value }}
                    </span>
                    <span class="inline-flex items-center gap-1 rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700 ring-1 ring-blue-200">
                        {{ $donation->type === \App\Enums\DonationType::Recurring ? 'Recurring' : 'One-time' }}
                    </span>
                </div>
            </div>
        @empty
            <div class="rounded-xl border border-stone-200/60 bg-white px-8 py-12 text-center">
                <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-stone-100">
                    <svg class="h-6 w-6 text-stone-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                    </svg>
                </div>
                <p class="text-sm font-medium text-stone-700">No donations yet</p>
                <p class="mt-1 text-sm text-stone-500">Your giving history will appear here once you make a donation.</p>
            </div>
        @endforelse
    </div>

    @if ($donations->hasPages())
        <div class="mt-6">
            {{ $donations->links() }}
        </div>
    @endif
@endsection
