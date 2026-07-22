<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    @include('emails.partials.receipt-styles')
    <style>
        .stmt-date { white-space: nowrap; color: #64748b; }
        .stmt-type { color: #64748b; font-size: 10px; }
        table.items td.num, table.items th.num { text-align: right; white-space: nowrap; }
        .summary { margin-top: 18px; width: 100%; border-collapse: separate; border-spacing: 16px 0; }
        .summary td { width: 33.33%; background: #f1f5f9; padding: 14px 16px; vertical-align: top; }
        .summary .label { font-size: 9px; font-weight: 700; color: #0f766e; text-transform: uppercase; letter-spacing: 0.6px; }
        .summary .value { font-size: 16px; font-weight: 700; color: #1a1a2e; margin-top: 4px; }
    </style>
</head>
<body>
    @php
        $currencyTotals = $donations->groupBy(fn ($d) => strtolower((string) $d->currency))
            ->map(fn ($group) => $group->sum(fn ($d) => (float) $d->gross_amount));

        $grandMyr = $donations->sum(fn ($d) => (float) ($d->base_amount ?? $d->gross_amount));
        $isMixed = $currencyTotals->count() > 1;

        $logoData = null;
        if (filled($organization->logo_path) && \Illuminate\Support\Facades\Storage::disk('public')->exists($organization->logo_path)) {
            $logoMime = \Illuminate\Support\Facades\Storage::disk('public')->mimeType($organization->logo_path) ?: 'image/png';
            $logoData = 'data:'.$logoMime.';base64,'.base64_encode(\Illuminate\Support\Facades\Storage::disk('public')->get($organization->logo_path));
        }
        $orgInitial = strtoupper(mb_substr(trim($organization->name), 0, 1));

        $addressParts = array_filter([$organization->city, $organization->state, $organization->country]);
        $contactParts = array_filter([implode(', ', $addressParts), $organization->contact_email, $organization->contact_phone]);
        $brand = parse_url((string) config('app.url'), PHP_URL_HOST) ?: config('app.name');
    @endphp

    {{-- Header --}}
    <table class="header-table">
        <tr>
            <td style="vertical-align: middle;">
                @if ($logoData)
                    <img class="logo" src="{{ $logoData }}" alt=""><span class="org-name">{{ $organization->name }}</span>
                @else
                    <span class="badge">{{ $orgInitial }}</span><span class="org-name">{{ $organization->name }}</span>
                @endif
            </td>
            <td style="text-align: right; vertical-align: middle;">
                <div class="doc-title">Annual Donation Statement</div>
                <div class="doc-subtitle">Year {{ $year }}</div>
            </td>
        </tr>
    </table>
    <hr class="rule">

    {{-- Donor + period --}}
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 4px;">
        <tr>
            <td style="width: 60%; vertical-align: top;" class="col-block">
                <div class="section-label" style="margin-bottom: 6px;">Statement For</div>
                <div class="name">{{ $donor->name }}</div>
                <div class="meta">{{ $donor->email }}</div>
            </td>
            <td style="width: 40%; vertical-align: top; text-align: right;" class="col-block">
                <div class="section-label" style="margin-bottom: 6px;">Period</div>
                <div class="name">1 Jan – 31 Dec {{ $year }}</div>
                <div class="meta">Issued {{ myrTime(now(), format: 'd F Y') }}</div>
            </td>
        </tr>
    </table>

    {{-- Line items --}}
    <table class="items" style="margin-top: 16px;">
        <tr>
            <th style="width: 18%;">Date</th>
            <th>Campaign</th>
            <th style="width: 16%;">Type</th>
            <th class="num" style="width: 20%;">Amount</th>
        </tr>
        @foreach ($donations as $donation)
            <tr>
                <td class="stmt-date">{{ myrTime($donation->created_at, withLabel: false, format: 'd M Y') }}</td>
                <td>
                    {{ $donation->campaign->title }}
                    <span class="stmt-type">· {{ $donation->invoice_number }}</span>
                </td>
                <td class="stmt-type">{{ $donation->type === \App\Enums\DonationType::Recurring ? 'Recurring' : 'One-time' }}</td>
                <td class="num">{{ $donation->formatted_amount }}</td>
            </tr>
        @endforeach
        <tr class="total">
            <td class="label" colspan="3">Total Donated ({{ $donations->count() }} {{ \Illuminate\Support\Str::plural('donation', $donations->count()) }})</td>
            <td class="num amount">MYR {{ number_format($grandMyr, 2) }}</td>
        </tr>
    </table>

    @if ($isMixed)
        <p class="note" style="margin-top: 10px;">
            Includes donations across multiple currencies
            ({{ collect($currencyTotals)->map(fn ($total, $cur) => strtoupper($cur).' '.number_format($total, 2))->implode(', ') }}).
            The total is converted to MYR at each donation's settled exchange rate.
        </p>
    @endif

    {{-- Summary --}}
    <table class="summary">
        <tr>
            <td>
                <div class="label">Total Donated</div>
                <div class="value">MYR {{ number_format($grandMyr, 2) }}</div>
            </td>
            <td>
                <div class="label">Donations</div>
                <div class="value">{{ $donations->count() }}</div>
            </td>
            <td>
                <div class="label">Campaigns Supported</div>
                <div class="value">{{ $donations->pluck('campaign_id')->unique()->count() }}</div>
            </td>
        </tr>
    </table>

    {{-- Footer --}}
    <div class="footer">
        <div class="fname">{{ $organization->name }}</div>
        @if (! empty($contactParts))
            <div class="fcontact">{{ implode('  |  ', $contactParts) }}</div>
        @endif
        <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
            <tr>
                <td class="fdisc">This statement was generated electronically by {{ config('app.name') }} and does not require a signature.</td>
                <td class="fbrand" style="text-align: right; white-space: nowrap;">{{ $brand }}</td>
            </tr>
        </table>
    </div>
</body>
</html>
