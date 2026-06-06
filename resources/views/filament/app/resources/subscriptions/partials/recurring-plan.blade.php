@php
$record = $record ?? null;
if (! $record) return;
@endphp

<div class="px-6 py-4 space-y-3 text-sm">
    <div class="grid items-baseline gap-x-6" style="grid-template-columns: 240px 1fr;">
        <span class="text-gray-500 dark:text-gray-400">Amount</span>
        <span class="text-gray-900 dark:text-gray-100 font-medium">{{ strtoupper($record->currency ?? 'MYR') }} {{ number_format((float) $record->amount, 2) }}</span>
    </div>

    <div class="grid items-baseline gap-x-6" style="grid-template-columns: 240px 1fr;">
        <span class="text-gray-500 dark:text-gray-400">Interval</span>
        <span class="text-gray-900 dark:text-gray-100 font-medium">{{ str($record->interval->value)->headline() }}</span>
    </div>

    <div class="grid items-baseline gap-x-6" style="grid-template-columns: 240px 1fr;">
        <span class="text-gray-500 dark:text-gray-400">Status</span>
        <span class="text-gray-900 dark:text-gray-100 font-medium">{{ str($record->status->value)->headline() }}</span>
    </div>

    @if ($record->stripe_subscription_id)
        <div class="grid items-center gap-x-6" style="grid-template-columns: 240px 1fr;">
            <span class="text-gray-500 dark:text-gray-400">Stripe Subscription ID</span>
            <div class="flex items-center gap-1.5 min-w-0">
                <span class="text-gray-900 dark:text-gray-100 font-mono truncate">{{ $record->stripe_subscription_id }}</span>
                <button
                    type="button"
                    @click="navigator.clipboard.writeText('{{ $record->stripe_subscription_id }}'); $dispatch('notify', { message: 'Copied' })"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors shrink-0"
                    title="Copy"
                >
                    <x-heroicon-o-clipboard-document class="size-3.5" />
                </button>
            </div>
        </div>
    @endif

    @if ($record->stripe_price_id)
        <div class="grid items-center gap-x-6" style="grid-template-columns: 240px 1fr;">
            <span class="text-gray-500 dark:text-gray-400">Stripe Price ID</span>
            <div class="flex items-center gap-1.5 min-w-0">
                <span class="text-gray-900 dark:text-gray-100 font-mono truncate">{{ $record->stripe_price_id }}</span>
                <button
                    type="button"
                    @click="navigator.clipboard.writeText('{{ $record->stripe_price_id }}'); $dispatch('notify', { message: 'Copied' })"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors shrink-0"
                    title="Copy"
                >
                    <x-heroicon-o-clipboard-document class="size-3.5" />
                </button>
            </div>
        </div>
    @endif

    <div class="grid items-baseline gap-x-6" style="grid-template-columns: 240px 1fr;">
        <span class="text-gray-500 dark:text-gray-400">Period Start</span>
        <span class="text-gray-900 dark:text-gray-100 font-medium">{{ $record->current_period_start?->format('d M Y') ?? '—' }}</span>
    </div>

    <div class="grid items-baseline gap-x-6" style="grid-template-columns: 240px 1fr;">
        <span class="text-gray-500 dark:text-gray-400">Period End</span>
        <span class="text-gray-900 dark:text-gray-100 font-medium">{{ $record->current_period_end?->format('d M Y') ?? '—' }}</span>
    </div>

    @if ($record->paused_until)
        <div class="grid items-baseline gap-x-6" style="grid-template-columns: 240px 1fr;">
            <span class="text-gray-500 dark:text-gray-400">Paused Until</span>
            <span class="text-gray-900 dark:text-gray-100 font-medium">{{ $record->paused_until->format('d M Y') }}</span>
        </div>
    @endif

    @if ($record->cancelled_at)
        <div class="grid items-baseline gap-x-6" style="grid-template-columns: 240px 1fr;">
            <span class="text-gray-500 dark:text-gray-400">Cancelled At</span>
            <span class="text-gray-900 dark:text-gray-100 font-medium">{{ $record->cancelled_at->format('d M Y') }}</span>
        </div>
    @endif

    <div class="grid items-baseline gap-x-6" style="grid-template-columns: 240px 1fr;">
        <span class="text-gray-500 dark:text-gray-400">Cancel at Period End</span>
        <span class="text-gray-900 dark:text-gray-100 font-medium">{{ $record->cancel_at_period_end ? 'Yes' : 'No' }}</span>
    </div>

    <div class="grid items-baseline gap-x-6" style="grid-template-columns: 240px 1fr;">
        <span class="text-gray-500 dark:text-gray-400">Cover Fee</span>
        <span class="text-gray-900 dark:text-gray-100 font-medium">{{ $record->cover_fee ? 'Yes' : 'No' }}</span>
    </div>
</div>
