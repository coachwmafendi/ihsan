@php
    $googleTagManagerId = config('services.google.gtm_id');
@endphp

@if (filled($googleTagManagerId))
    <!-- Google Tag Manager (noscript) -->
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ urlencode($googleTagManagerId) }}"
    height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    <!-- End Google Tag Manager (noscript) -->
@endif
