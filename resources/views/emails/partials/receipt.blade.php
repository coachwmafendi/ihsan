@php
    $org = $donation->campaign->organization;
    $donor = $donation->donor;

    $currency = strtoupper((string) $donation->currency);
    $hasCoveredFee = $donation->donor_fee_covered > 0;
    $isForeign = strtolower((string) $donation->currency) !== 'myr' && $donation->base_amount !== null;

    $statusLabel = $donation->status === \App\Enums\DonationStatus::Succeeded
        ? 'Successful'
        : ucfirst($donation->status->value);

    $orgInitial = strtoupper(mb_substr(trim($org->name), 0, 1));

    $logoData = null;
    if (filled($org->logo_path) && \Illuminate\Support\Facades\Storage::disk('public')->exists($org->logo_path)) {
        $logoMime = \Illuminate\Support\Facades\Storage::disk('public')->mimeType($org->logo_path) ?: 'image/png';
        $logoData = 'data:'.$logoMime.';base64,'.base64_encode(\Illuminate\Support\Facades\Storage::disk('public')->get($org->logo_path));
    }

    $addressParts = array_filter([$org->city, $org->state, $org->country]);
    $contactParts = array_filter([implode(', ', $addressParts), $org->contact_email, $org->contact_phone]);

    $brand = parse_url((string) config('app.url'), PHP_URL_HOST) ?: config('app.name');
@endphp

{{-- Header --}}
<table class="header-table">
    <tr>
        <td style="vertical-align: middle;">
            @if ($logoData)
                <img class="logo" src="{{ $logoData }}" alt=""><span class="org-name">{{ $org->name }}</span>
            @else
                <span class="badge">{{ $orgInitial }}</span><span class="org-name">{{ $org->name }}</span>
            @endif
        </td>
        <td style="text-align: right; vertical-align: middle;">
            <div class="doc-title">Donation Receipt</div>
            <div class="doc-subtitle">Electronic receipt</div>
        </td>
    </tr>
</table>
<hr class="rule">

{{-- Thank you --}}
<h1 class="thanks">Thank you, {{ $donor->name }}.</h1>
<p class="thanks-sub">Your donation has been received and will support your selected campaign.</p>

{{-- Receipt details + Payment status --}}
<table style="width: 100%; border-collapse: separate; border-spacing: 16px 0; margin: 0 -16px 24px;">
    <tr>
        <td style="width: 50%; vertical-align: top;" class="panel">
            <div class="section-label" style="margin-bottom: 8px;">Receipt Details</div>
            <div class="kv" style="margin-bottom: 3px;"><span class="k">Receipt no.</span> &nbsp;<span class="v">{{ $donation->invoice_number }}</span></div>
            <div class="kv"><span class="k">Received on</span> &nbsp;<span class="v">{{ myrTime($donation->created_at, format: 'd F Y, g:i A') }}</span></div>
        </td>
        <td style="width: 50%; vertical-align: top;" class="panel">
            <div class="section-label" style="margin-bottom: 8px;">Payment Status</div>
            <div class="status-value">{{ $statusLabel }}</div>
            <div class="kv"><span class="k">Transaction no.: {{ $donation->public_id }}</span></div>
        </td>
    </tr>
</table>

{{-- Donated by + Campaign --}}
<table style="width: 100%; border-collapse: collapse; margin-bottom: 22px;">
    <tr>
        <td style="width: 50%; vertical-align: top;" class="col-block">
            <div class="section-label" style="margin-bottom: 6px;">Donated By</div>
            <div class="name">{{ $donor->name }}</div>
            <div class="meta">{{ $donor->email }}</div>
        </td>
        <td style="width: 50%; vertical-align: top;" class="col-block">
            <div class="section-label" style="margin-bottom: 6px;">Campaign</div>
            <div class="name">{{ $donation->campaign->title }}</div>
            <div class="meta">Type: {{ $donation->type === \App\Enums\DonationType::Recurring ? 'Recurring donation' : 'One-time donation' }}</div>
        </td>
    </tr>
</table>

{{-- Line items --}}
<table class="items">
    <tr>
        <th>Description</th>
        <th class="right">Amount ({{ $currency }})</th>
    </tr>
    <tr>
        <td>Donation to {{ $donation->campaign->title }}</td>
        <td class="right">{{ number_format((float) $donation->gross_amount, 2) }}</td>
    </tr>
    @if ($hasCoveredFee)
        <tr>
            <td>Processing fee (covered by donor)</td>
            <td class="right">{{ number_format((float) $donation->donor_fee_covered, 2) }}</td>
        </tr>
    @endif
    <tr class="total">
        <td class="label">Total Paid</td>
        <td class="right amount">{{ $currency }} {{ number_format((float) $donation->total_charged, 2) }}</td>
    </tr>
</table>

@if ($isForeign)
    <p class="note" style="margin-top: 10px;">Approx. MYR {{ number_format((float) $donation->base_amount + $donation->feeCoveredInBaseCurrency(), 2) }} at the settled exchange rate.</p>
@endif

<div class="paymeta">Payment method: {{ str_replace('****', '••••', $donation->payment_method_display) }}</div>
@if ($hasCoveredFee)
    <div class="note">Note: Total paid includes the donation amount and the processing fee selected by the donor.</div>
@endif

@if ($donation->donor_message)
    <div class="quote">&ldquo;{{ $donation->donor_message }}&rdquo;</div>
@endif

@if ($donation->type === \App\Enums\DonationType::Recurring)
    <div class="note">This is a recurring donation. A receipt is issued for each successful payment.</div>
@endif

{{-- Footer --}}
<div class="footer">
    <div class="fname">{{ $org->name }}</div>
    @if (! empty($contactParts))
        <div class="fcontact">{{ implode('  |  ', $contactParts) }}</div>
    @endif
    <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
        <tr>
            <td class="fdisc">This receipt was generated electronically by {{ config('app.name') }} and does not require a signature.</td>
            <td class="fbrand" style="text-align: right; white-space: nowrap;">{{ $brand }}</td>
        </tr>
    </table>
</div>
