<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="font-family: 'Plus Jakarta Sans', sans-serif; line-height: 1.6; color: #1a1a2e;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #dc2626;">Payment Failed</h1>

        <p>Hi <strong>{{ $subscription->campaign->organization->name }}</strong>,</p>

        <p>A recurring payment has failed for campaign <strong>{{ $subscription->campaign->title }}</strong>.</p>

        <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
            <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Subscriber</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{ $subscription->donor->name }}</td></tr>
            <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Campaign</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{ $subscription->campaign->title }}</td></tr>
            <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Amount</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; font-weight: 600;">{{ $subscription->currency_symbol }} {{ number_format($subscription->amount, 2) }}</td></tr>
            <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Frequency</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{ ucfirst($subscription->interval->value) }}</td></tr>
            <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Retry Count</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{ $subscription->retry_count ?? 0 }}</td></tr>
            @if ($failureMessage)
                <tr><td style="padding: 8px; color: #64748b;">Error</td><td style="padding: 8px; color: #dc2626;">{{ $failureMessage }}</td></tr>
            @endif
        </table>

        <p>Stripe will automatically retry this payment. Please monitor the situation.</p>

        <p style="font-size: 0.875rem; color: #94a3b8;">
            You are receiving this because your organisation has failed payment notifications enabled.
        </p>
    </div>
</body>
</html>
