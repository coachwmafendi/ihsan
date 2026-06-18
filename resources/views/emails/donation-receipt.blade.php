<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="font-family: 'Plus Jakarta Sans', sans-serif; line-height: 1.6; color: #1a1a2e;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #0f766e;">Thank you for your donation!</h1>

        <p>Hi <strong>{{ $donation->donor->name }}</strong>,</p>

        @php
            $symbol = $donation->currency_symbol;
            $hasCoveredFee = $donation->donor_fee_covered > 0;
            $totalCharged = $donation->total_charged_with_conversion;
        @endphp

        <p>Your donation of <strong>{{ $donation->total_charged_with_conversion }}</strong> to <strong>{{ $donation->campaign->title }}</strong> has been received successfully.</p>

        @if ($donation->type === \App\Enums\DonationType::Recurring)
            <p><em>This is a recurring donation. You will receive a receipt for each successful payment.</em></p>
        @endif

        <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
            @if ($hasCoveredFee)
                <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Donation</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; font-weight: 600;">{{ $donation->formatted_amount }}</td></tr>
                <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Processing Fee</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">{{ $symbol }} {{ number_format((float) $donation->donor_fee_covered, 2) }}</td></tr>
                <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b; font-weight: 600;">Total Charged</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; font-weight: 700;">{{ $totalCharged }}</td></tr>
            @else
                <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Amount</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; font-weight: 600;">{{ $donation->total_charged_with_conversion }}</td></tr>
            @endif
            <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Campaign</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{ $donation->campaign->title }}</td></tr>
            <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Organization</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{ $donation->campaign->organization->name }}</td></tr>
            <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Date</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{ $donation->created_at->format('d M Y, g:i A') }}</td></tr>
            <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Payment Method</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{ $donation->payment_method_display }}</td></tr>
            <tr><td style="padding: 8px; color: #64748b;">Status</td><td style="padding: 8px; color: #16a34a; font-weight: 600;">Successful</td></tr>
        </table>

        <p>Thank you for your support!</p>

        <p style="font-size: 0.875rem; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 16px; margin-top: 24px;">
            <a href="{{ route('donorportal.dashboard', $donation->campaign->organization) }}" style="color: #0d9488; text-decoration: underline;">Go to your donor portal</a>
            to view your donation history, manage subscriptions, and download receipts.
        </p>

        @include('emails.partials.org-footer', ['organization' => $donation->campaign->organization])
    </div>
</body>
</html>
