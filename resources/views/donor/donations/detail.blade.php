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

<div class="flex h-full flex-col" @click.stop>
    <div class="border-b border-slate-100 px-6 py-5">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Donation amount</p>
                <p class="mt-1 text-2xl font-black text-slate-900">{{ $donation->formatted_amount }}</p>
            </div>
            <div class="flex flex-shrink-0 items-center gap-1.5">
                <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-bold uppercase tracking-wide {{ $statusClass }}">
                    {{ $statusLabel }}
                </span>
                <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-bold uppercase tracking-wide {{ $typeClass }}">
                    {{ $typeLabel }}
                </span>
            </div>
        </div>
    </div>

    <div class="flex-1 overflow-y-auto px-6 py-6">
        <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Campaign</p>
            <p class="mt-1 font-bold text-slate-900">{{ $donation->campaign->title }}</p>
            <p class="mt-0.5 text-sm text-slate-500">{{ $donation->campaign->organization->name }}</p>
        </div>

        <div class="mt-5 grid grid-cols-2 gap-4">
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Date</p>
                <p class="mt-1 text-sm font-semibold text-slate-700">{{ myrTime($donation->created_at, withLabel: false, format: 'd M Y') }}</p>
                <p class="text-xs text-slate-400">{{ myrTime($donation->created_at, withLabel: false, format: 'h:i A') }}</p>
            </div>
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Payment method</p>
                <div class="mt-1 flex items-center gap-2">
                    <x-dynamic-component :component="$donation->card_icon_component" class="h-5 w-auto flex-shrink-0" />
                    <span class="text-sm font-semibold text-slate-700">{{ $donation->payment_method_display }}</span>
                </div>
            </div>
            @if ($donation->invoice_number)
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Invoice number</p>
                    <p class="mt-1 text-sm font-semibold text-slate-700">{{ $donation->invoice_number }}</p>
                </div>
            @endif
            @if ($donation->stripe_payment_intent_id)
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Transaction reference</p>
                    <p class="mt-1 text-sm font-semibold text-slate-700 break-all">{{ $donation->stripe_payment_intent_id }}</p>
                </div>
            @endif
            @if ($donation->subscription)
                <div class="col-span-2">
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Recurring plan</p>
                    <p class="mt-1 text-sm font-semibold text-slate-700">
                        {{ $donation->subscription->displayAmount() }}
                        / {{ $donation->subscription->interval->value }}
                    </p>
                </div>
            @endif
        </div>

        <div class="mt-6 rounded-xl border border-slate-100 p-4">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Payment breakdown</p>
            <div class="mt-3 space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-slate-500">Donation amount</span>
                    <span class="font-semibold text-slate-700">{{ $donation->formatted_amount }}</span>
                </div>
                @if ($donation->donor_fee_covered > 0)
                    <div class="flex justify-between">
                        <span class="text-slate-500">Fee covered</span>
                        <span class="font-semibold text-emerald-600">+ {{ $donation->displayAmount((float) $donation->donor_fee_covered) }}</span>
                    </div>
                @endif
                <div class="flex justify-between">
                    <span class="text-slate-500">Processing fee</span>
                    <span class="font-semibold text-slate-700">- {{ $donation->displayAmount((float) $donation->processing_fee) }}</span>
                </div>
                <div class="border-t border-slate-100 pt-2 flex justify-between">
                    <span class="font-semibold text-slate-900">Net received</span>
                    <span class="font-black text-slate-900">{{ $donation->displayAmount((float) $donation->net_amount) }}</span>
                </div>
            </div>
        </div>

        @if ($donation->donor_message)
            <div class="mt-5 rounded-xl border border-slate-100 p-4">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Your message</p>
                <p class="mt-1 text-sm text-slate-700 italic">“{{ $donation->donor_message }}”</p>
            </div>
        @endif
    </div>

    @if ($donation->status === \App\Enums\DonationStatus::Succeeded)
        <div class="border-t border-slate-100 px-6 py-4">
            <a href="{{ route('donorportal.donations.receipt.download', ['organization' => $organization, 'donation' => $donation]) }}"
               class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-700">
                <x-heroicon name="arrow-down-tray" class="h-4 w-4" />
                Download receipt
            </a>
        </div>
    @endif
</div>
