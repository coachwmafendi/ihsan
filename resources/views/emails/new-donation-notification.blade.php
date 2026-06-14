<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="font-family: 'Plus Jakarta Sans', sans-serif; line-height: 1.6; color: #1a1a2e;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #16a34a;">New Donation Received 🎉</h1>

        <p>Hi <strong>{{ $donation->campaign->organization->name }}</strong>,</p>

        @php
            $symbol = $donation->currency_symbol;
            $hasCoveredFee = $donation->donor_fee_covered > 0;
            $netAmount = $symbol.' '.number_format((float) $donation->net_amount, 2);
        @endphp

        <p>You have received a new donation of <strong>{{ $amountDisplay }}</strong> for <strong>{{ $donation->campaign->title }}</strong>.</p>

        <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
            <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Donor</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{ $donation->donor->name }}</td></tr>
            <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Donation</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; font-weight: 600;">{{ $amountDisplay }}</td></tr>
            @if ($hasCoveredFee)
                <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Fee Covered by Donor</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">{{ $symbol }} {{ number_format((float) $donation->donor_fee_covered, 2) }}</td></tr>
            @endif
            <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Net to Organisation</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; font-weight: 600; color: #16a34a;">{{ $netAmount }}</td></tr>
            <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Campaign</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{ $donation->campaign->title }}</td></tr>
            <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Type</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{ ucwords(str_replace('_', ' ', $donation->type->value)) }}</td></tr>
            <tr><td style="padding: 8px; color: #64748b;">Date</td><td style="padding: 8px;">{{ $donation->created_at->format('d M Y, h:i A') }}</td></tr>
        </table>

        <p style="font-size: 0.875rem; color: #94a3b8;">
            You are receiving this because your organisation has new donation notifications enabled.
        </p>
        <p style="margin-top: 8px; font-size: 12px; color: #94a3b8;">Sent with ❤️ from {{ config('app.name') }}</p>

    </div>
</body>
</html>
