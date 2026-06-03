@props(['currency', 'class' => ''])

@php $currency = strtolower($currency); @endphp

@if ($currency === 'myr')
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 14" {{ $attributes->merge(['class' => 'block shrink-0 overflow-hidden rounded-sm ' . $class]) }}>
    <rect width="20" height="14" fill="#fff"/>
    <rect y="0"  width="20" height="1" fill="#CC0001"/>
    <rect y="2"  width="20" height="1" fill="#CC0001"/>
    <rect y="4"  width="20" height="1" fill="#CC0001"/>
    <rect y="6"  width="20" height="1" fill="#CC0001"/>
    <rect y="8"  width="20" height="1" fill="#CC0001"/>
    <rect y="10" width="20" height="1" fill="#CC0001"/>
    <rect y="12" width="20" height="1" fill="#CC0001"/>
    <rect width="10" height="7" fill="#010066"/>
    <circle cx="3.5" cy="3.5" r="2"    fill="#FFD100"/>
    <circle cx="4.35" cy="3.5" r="1.55" fill="#010066"/>
    <polygon fill="#FFD100" points="
        8,1.7   8.156,2.817 8.781,1.878 8.438,2.954
        9.407,2.378 8.631,3.196 9.755,3.099 8.7,3.5
        9.755,3.901 8.631,3.804 9.407,4.622 8.438,4.046
        8.781,5.122 8.156,4.182 8,5.3   7.844,4.182
        7.219,5.122 7.563,4.046 6.593,4.622 7.369,3.804
        6.245,3.901 7.3,3.5   6.245,3.099 7.369,3.196
        6.593,2.378 7.563,2.954 7.219,1.878 7.844,2.817
    "/>
</svg>

@elseif ($currency === 'usd')
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 19 10" {{ $attributes->merge(['class' => 'block shrink-0 overflow-hidden rounded-sm ' . $class]) }}>
    <rect width="19" height="10" fill="#fff"/>
    <rect y="0"     width="19" height="0.769" fill="#B22234"/>
    <rect y="1.538" width="19" height="0.769" fill="#B22234"/>
    <rect y="3.077" width="19" height="0.769" fill="#B22234"/>
    <rect y="4.615" width="19" height="0.769" fill="#B22234"/>
    <rect y="6.154" width="19" height="0.769" fill="#B22234"/>
    <rect y="7.692" width="19" height="0.769" fill="#B22234"/>
    <rect y="9.231" width="19" height="0.769" fill="#B22234"/>
    <rect width="7.6" height="5.385" fill="#3C3B6E"/>
    <defs>
        <pattern id="us-stars" x="0.633" y="0.269" width="1.267" height="1.077" patternUnits="userSpaceOnUse">
            <circle cx="0" cy="0" r="0.18" fill="#fff"/>
        </pattern>
    </defs>
    <rect width="7.6" height="5.385" fill="url(#us-stars)"/>
</svg>

@elseif ($currency === 'sgd')
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 3 2" {{ $attributes->merge(['class' => 'block shrink-0 overflow-hidden rounded-sm ' . $class]) }}>
    <rect width="3" height="2" fill="#fff"/>
    <rect width="3" height="1" fill="#EF3340"/>
    <circle cx="0.54" cy="0.5" r="0.33" fill="#fff"/>
    <circle cx="0.7"  cy="0.5" r="0.27" fill="#EF3340"/>
    <polygon fill="#fff" points="1.12,0.18 1.148,0.274 1.245,0.274 1.166,0.33 1.194,0.424 1.12,0.368 1.046,0.424 1.074,0.33 0.995,0.274 1.092,0.274"/>
    <polygon fill="#fff" points="1.396,0.36 1.424,0.454 1.521,0.454 1.442,0.51 1.47,0.604 1.396,0.548 1.322,0.604 1.35,0.51 1.271,0.454 1.368,0.454"/>
    <polygon fill="#fff" points="1.302,0.65 1.33,0.744 1.427,0.744 1.348,0.8 1.376,0.894 1.302,0.838 1.228,0.894 1.256,0.8 1.177,0.744 1.274,0.744"/>
    <polygon fill="#fff" points="1.0,0.65  1.028,0.744 1.125,0.744 1.046,0.8  1.074,0.894 1.0,0.838  0.926,0.894 0.954,0.8  0.875,0.744 0.972,0.744"/>
    <polygon fill="#fff" points="0.844,0.36 0.872,0.454 0.969,0.454 0.89,0.51  0.918,0.604 0.844,0.548 0.77,0.604  0.798,0.51  0.719,0.454 0.816,0.454"/>
</svg>

@endif
