<x-filament-panels::page>
    <div class="ihsan-admin-page">
        <div class="grid gap-4 md:grid-cols-3">
            <x-admin.metric-card icon="heroicon-o-document-text" label="Outstanding" :value="'MYR '.$totalOutstanding" note="Unpaid invoices" />
            <x-admin.metric-card icon="heroicon-o-check-circle" label="Collected" :value="'MYR '.$totalCollected" note="Paid invoices" />
            <x-admin.metric-card icon="heroicon-o-paper-airplane" label="Sent This Month" :value="$invoicesSent" note="Invoices generated" />
        </div>

        {{ $this->table }}
    </div>
</x-filament-panels::page>
