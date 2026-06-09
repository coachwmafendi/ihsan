<x-filament-widgets::widget>
<div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
    <x-ui.stat-card
        label="Total Supporters"
        value="{{ $totalDonors }}"
        subtext="All-time donors"
    />
    <x-ui.stat-card
        label="New This Month"
        value="{{ $newThisMonth }}"
        subtext="First-time donors"
        trend="This month"
        trendColor="success"
    />
    <x-ui.stat-card
        label="Total Donated"
        value="MYR {{ $totalDonated }}"
        subtext="From paid donations"
    />
    <x-ui.stat-card
        label="Active Recurring"
        value="{{ $activeRecurring }}"
        subtext="Supporters with active plans"
    />
</div>
</x-filament-widgets::widget>
