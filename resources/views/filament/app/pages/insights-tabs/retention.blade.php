<div>
    <x-ui.section-header
        title="Retention"
        subtitle="Donor loyalty and repeat giving"
    />

    <div class="mt-8 grid gap-4 md:grid-cols-4">
        <x-ui.stat-card
            label="Total Donors"
            value="{{ $retentionOverview['totalDonors'] }}"
        />
        <x-ui.stat-card
            label="Repeat Donors"
            value="{{ $retentionOverview['repeatDonors'] }}"
        />
        <x-ui.stat-card
            label="Repeat Rate"
            value="{{ $retentionOverview['repeatRate'] }}"
        />
        <x-ui.stat-card
            label="New This Month"
            value="{{ $retentionOverview['newThisMonth'] }}"
            subtext="{{ $retentionOverview['returningThisMonth'] }} returning"
        />
    </div>
</div>
