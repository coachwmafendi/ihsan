@php
    $previewTitle = 'Ihsan Admin — Platform Admin Console';
    $previewDescription = 'Sign in to the Ihsan platform admin console to manage organizations, donations, processing fees, monthly invoices, revenue and fraud prevention for the Ihsan donation platform. Authorised staff only.';
    $previewImage = asset('images/og-admin.png');
@endphp

{{-- Staff-only console: link previews yes, search results no. --}}
<meta name="robots" content="noindex, nofollow">
<meta name="description" content="{{ $previewDescription }}">
<meta name="theme-color" content="#1a120e">

<meta property="og:type" content="website">
<meta property="og:site_name" content="Ihsan">
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
