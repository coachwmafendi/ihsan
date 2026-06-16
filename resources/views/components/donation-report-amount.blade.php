@props(['donation'])

<span
    class="text-sm font-semibold text-slate-900"
    @if ($donation->is_report_approximate)
        title="{{ $donation->formatted_original_amount }}"
    @endif
>
    {{ $donation->formatted_report_amount }}
</span>
