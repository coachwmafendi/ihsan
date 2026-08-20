@php
    $cloudflareAnalyticsToken = config('services.cloudflare.analytics_token');
@endphp

@if (filled($cloudflareAnalyticsToken))
    <!-- Cloudflare Web Analytics -->
    <script type="module" src="https://static.cloudflareinsights.com/beacon.min.js" data-cf-beacon='{"token": "{{ $cloudflareAnalyticsToken }}"}'></script>
    <!-- End Cloudflare Web Analytics -->
@endif
