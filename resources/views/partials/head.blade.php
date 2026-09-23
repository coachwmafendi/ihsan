<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>
    {{ filled($title ?? null) ? $title.' - '.config('app.name', 'Laravel') : config('app.name', 'Laravel') }}
</title>

<link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
<link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
<link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

{{--
    No @fonts here. The directive only emits Instrument Sans, which nothing
    on these pages asks for: --font-sans is Inter, and Inter arrives with
    app.css. Emitting it cost the checkout six font files and 72 KB before
    a donor had read the amount.
--}}
@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance

@include('partials.cloudflare-analytics')
