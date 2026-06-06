@php
$donations = $record->donations()->latest()->get();
@endphp

@if ($donations->isEmpty())
    <p class="text-sm text-gray-500 dark:text-gray-400">No installments yet.</p>
@else
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="text-xs text-gray-500 uppercase bg-gray-50 dark:bg-gray-800 dark:text-gray-400">
                <tr>
                    <th class="px-4 py-2">Date</th>
                    <th class="px-4 py-2">Amount</th>
                    <th class="px-4 py-2">Status</th>
                    <th class="px-4 py-2">Receipt</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($donations as $donation)
                    <tr class="border-b dark:border-gray-700">
                        <td class="px-4 py-2">{{ $donation->created_at->format('d M Y') }}</td>
                        <td class="px-4 py-2">{{ strtoupper($donation->currency) }} {{ number_format((float) $donation->gross_amount, 2) }}</td>
                        <td class="px-4 py-2">
                            @php
                                $statusColor = match (ucfirst($donation->status->value)) {
                                    'Pending' => 'bg-gray-100 text-gray-700',
                                    'Succeeded' => 'bg-green-50 text-green-700',
                                    'Failed' => 'bg-red-50 text-red-700',
                                    'Refunded' => 'bg-amber-50 text-amber-700',
                                    default => 'bg-gray-100 text-gray-700',
                                };
                            @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $statusColor }}">
                                {{ ucfirst($donation->status->value) }}
                            </span>
                        </td>
                        <td class="px-4 py-2">
                            @if ($donation->status->value === 'succeeded' && $donation->invoice_number)
                                <a href="{{ route('donations.receipt.download', $donation) }}" target="_blank" class="text-primary-600 hover:underline text-xs">
                                    Download
                                </a>
                            @else
                                <span class="text-gray-400 text-xs">—</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
