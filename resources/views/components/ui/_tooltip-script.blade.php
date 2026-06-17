@once
    @php
        $tooltipScriptPath = resource_path('js/tooltip.js');
    @endphp
    @if (file_exists($tooltipScriptPath))
        <script data-navigate-once>{!! file_get_contents($tooltipScriptPath) !!}</script>
    @endif
@endonce
