<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="font-family: 'Plus Jakarta Sans', sans-serif; line-height: 1.6; color: #1a1a2e;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #2563eb;">Subscription Amount Updated</h1>

        <p>Hi <strong>{{ $recipientName ?? 'there' }}</strong>,</p>

        <p>
            The recurring donation for <strong>{{ $subscription->campaign->title }}</strong>
            has been updated from <strong>{{ $subscription->currency_symbol }} {{ number_format($previousAmount, 2) }}</strong>
            to <strong>{{ $amountDisplay }}</strong> per {{ $subscription->interval->value }}.
        </p>

        <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Donor</td>
                <td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{ $subscription->donor->name }}</td>
            </tr>
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Previous Amount</td>
                <td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{ $subscription->currency_symbol }} {{ number_format($previousAmount, 2) }}</td>
            </tr>
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">New Amount</td>
                <td style="padding: 8px; border-bottom: 1px solid #e2e8f0; font-weight: 600;">{{ $amountDisplay }}</td>
            </tr>
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Frequency</td>
                <td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{ str($subscription->interval->value)->headline() }}</td>
            </tr>
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Campaign</td>
                <td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{ $subscription->campaign->title }}</td>
            </tr>
            <tr>
                <td style="padding: 8px; color: #64748b;">Date</td>
                <td style="padding: 8px;">{{ now()->format('d M Y, h:i A') }}</td>
            </tr>
        </table>

        <p style="font-size: 0.875rem; color: #94a3b8;">
            You are receiving this because a recurring donation amount was updated.
        </p>
        <p style="margin-top: 8px; font-size: 12px; color: #94a3b8;">Sent with ❤️ from {{ config('app.name') }}</p>
    </div>
</body>
</html>
