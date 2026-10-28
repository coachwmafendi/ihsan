@php
    $portalName = filled($organization->name ?? null) ? $organization->name : 'Ihsan';
    $previewTitle = $portalName.' — Donor Portal';
    $previewDescription = 'Sign in to the '.$portalName.' donor portal to see your donation history, download receipts and your annual statement, and manage your monthly giving. Secure sign-in with a magic link — no password needed.';
    $previewImage = asset('images/og-donor-portal.png');
@endphp

{{-- Donors' own pages: link previews yes, search results no. --}}
<meta name="robots" content="noindex, nofollow">
<meta name="description" content="{{ $previewDescription }}">
<meta name="theme-color" content="#0f172a">

<meta property="og:type" content="website">
<meta property="og:site_name" content="{{ $portalName }}">
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:title" content="{{ $previewTitle }}">
<meta property="og:description" content="{{ $previewDescription }}">
<meta property="og:image" content="{{ $previewImage }}">
<meta property="og:image:secure_url" content="{{ $previewImage }}">
<meta property="og:image:type" content="image/png">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="{{ $previewTitle }}">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $previewTitle }}">
<meta name="twitter:description" content="{{ $previewDescription }}">
<meta name="twitter:image" content="{{ $previewImage }}">
