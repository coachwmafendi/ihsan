@php
$donations = $record->donations()->where('status', 'succeeded')->latest()->get();
@endphp

@if ($donations->isEmpty())
    <p class="text-sm text-gray-500 dark:text-gray-400">No receipts available.</p>
@else
    <div class="space-y-2">
        @foreach ($donations as $donation)
            <div class="flex items-center justify-between px-4 py-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
                <div class="flex items-center gap-3">
                    <x-heroicon-o-document-text class="size-5 text-gray-400" />
                    <div>
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $donation->invoice_number ?? 'Receipt #' . $donation->id }}</p>
                        <p class="text-xs text-gray-500">{{ $donation->created_at->format('d M Y') }}</p>
                    </div>
                </div>
                <a href="{{ route('donations.receipt.download', $donation) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-xs font-medium text-primary-600 hover:text-primary-700">
                    <x-heroicon-o-arrow-down-tray class="size-3.5" />
                    Download
                </a>
            </div>
        @endforeach
    </div>
@endif
