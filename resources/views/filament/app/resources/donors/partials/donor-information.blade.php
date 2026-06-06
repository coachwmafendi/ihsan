@php
    use App\Models\Donor;

    /** @var Donor|null $donor */

    $language = match ($donor?->locale) {
        'en' => 'English',
        'ms' => 'Bahasa Melayu',
        default => $donor?->locale ?: '—',
    };

    $addressParts = array_filter([
        $donor?->address_line1,
        $donor?->address_line2,
        $donor?->address_city,
        $donor?->address_state,
        $donor?->address_postal_code,
        $donor?->country,
    ]);
    $address = $addressParts ? implode(', ', $addressParts) : '—';
@endphp

<div class="w-full" style="max-width: 100% !important;">
    <div class="divide-y divide-gray-100 dark:divide-gray-800">
        <div class="flex items-center py-4 gap-4">
            <span class="w-40 shrink-0 text-sm text-gray-500 dark:text-gray-400">Name</span>
            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $donor?->name ?? '—' }}</span>
        </div>

        <div class="flex items-center py-4 gap-4">
            <span class="w-40 shrink-0 text-sm text-gray-500 dark:text-gray-400">Email</span>
            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $donor?->email ?? '—' }}</span>
        </div>

        <div class="flex items-center py-4 gap-4">
            <span class="w-40 shrink-0 text-sm text-gray-500 dark:text-gray-400">Language</span>
            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $language }}</span>
        </div>

        <div class="flex items-center py-4 gap-4">
            <span class="w-40 shrink-0 text-sm text-gray-500 dark:text-gray-400">Phone</span>
            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $donor?->phone ?: '—' }}</span>
        </div>

        <div class="flex items-start py-4 gap-4">
            <span class="w-40 shrink-0 text-sm text-gray-500 dark:text-gray-400">Mailing address</span>
            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $address }}</span>
        </div>
    </div>
</div>
