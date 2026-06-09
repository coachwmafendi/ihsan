<div>
    <x-ui.section-header
        title="Frequencies"
        subtitle="One-time vs recurring donation breakdown"
    />

    <div class="mt-8 grid gap-4 md:grid-cols-2">
        @foreach ($frequencyBreakdown as $row)
            <x-ui.stat-card
                wire:key="freq-{{ $row['label'] }}"
                label="{{ $row['label'] }}"
                value="{{ $row['value'] }}"
            />
        @endforeach
    </div>
</div>
