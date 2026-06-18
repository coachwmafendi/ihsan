@props(['donation'])

@if ($donation->is_report_approximate)
    <x-ui.tooltip :text="$donation->formatted_original_amount">
        <span class="text-sm font-semibold text-slate-900">
            {{ $donation->formatted_report_amount }}
        </span>
    </x-ui.tooltip>
@else
    <span class="text-sm font-semibold text-slate-900">
        {{ $donation->formatted_report_amount }}
    </span>
@endif
