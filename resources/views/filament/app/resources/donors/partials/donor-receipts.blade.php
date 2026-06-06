@php
    use App\Models\Donation;

    /** @var \Illuminate\Support\Collection<int, Donation> $donations */
@endphp

<x-filament::section icon="heroicon-o-document-text">
    <x-slot name="heading">
        Receipts
    </x-slot>

    @if ($donations->isEmpty())
        <div class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
            No receipts available.
        </div>
    @else
        <div class="-mx-6 -mb-6 overflow-x-auto">
            <table class="w-full min-w-[760px] divide-y divide-gray-200 text-left dark:divide-gray-800">
                <thead>
                    <tr class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                        <th class="w-16 px-6 py-4"></th>
                        <th class="px-6 py-4">Receipt number</th>
                        <th class="px-6 py-4 text-right">Amount</th>
                        <th class="px-6 py-4">Donation date</th>
                        <th class="px-6 py-4">Issue date</th>
                        <th class="px-6 py-4"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @foreach ($donations as $donation)
                        <tr class="text-sm text-gray-700 dark:text-gray-300">
                            <td class="px-6 py-4">
                                <x-heroicon-o-check class="size-5 text-emerald-600 dark:text-emerald-400" />
                            </td>
                            <td class="px-6 py-4 text-base font-medium text-gray-900 dark:text-gray-100">
                                {{ $donation->invoice_number }}
                            </td>
                            <td class="px-6 py-4 text-right text-base font-semibold text-gray-900 dark:text-gray-100">
                                @if ($donation->currency !== 'myr' && $donation->base_amount !== null)
                                    <span class="font-medium text-gray-500 dark:text-gray-400">&asymp;</span>
                                    MYR {{ number_format((float) $donation->base_amount, 2) }}
                                @else
                                    {{ strtoupper($donation->currency) }} {{ number_format((float) $donation->gross_amount, 2) }}
                                @endif
                            </td>
                            <td class="px-6 py-4 text-base text-gray-900 dark:text-gray-100">
                                {{ $donation->created_at->format('M j, Y') }}
                            </td>
                            <td class="px-6 py-4 text-base text-gray-900 dark:text-gray-100">
                                {{ ($donation->receipt_sent_at ?? $donation->created_at)->format('M j, Y') }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a
                                    href="{{ route('donations.receipt.download', $donation) }}"
                                    target="_blank"
                                    rel="noopener"
                                    class="inline-flex items-center gap-2 text-base font-medium text-gray-500 transition-colors hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100"
                                >
                                    <x-heroicon-o-arrow-down-tray class="size-5" />
                                    Download
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-filament::section>
