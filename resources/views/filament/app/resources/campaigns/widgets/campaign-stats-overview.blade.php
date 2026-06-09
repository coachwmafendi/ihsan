<x-filament-widgets::widget>
<div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
    <x-ui.stat-card
        label="Total Campaigns"
        value="{{ $totalCampaigns }}"
        subtext="All campaigns"
    />
    <x-ui.stat-card
        label="Live Campaigns"
        value="{{ $liveCampaigns }}"
        subtext="Active campaigns"
        trend="Active now"
        trendColor="success"
    />
    <x-ui.stat-card
        label="Total Raised"
        value="MYR {{ $totalRaised }}"
        subtext="From paid donations"
    />
    <x-ui.stat-card
        label="Total Donors"
        value="{{ $totalDonors }}"
        subtext="Unique donors"
    />
</div>
</x-filament-widgets::widget>
