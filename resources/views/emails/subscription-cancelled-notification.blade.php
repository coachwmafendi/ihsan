<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="font-family: 'Plus Jakarta Sans', sans-serif; line-height: 1.6; color: #1a1a2e;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #f59e0b;">Subscription Cancelled</h1>

        <p>Hi <strong>{{ $subscription->campaign->organization->name }}</strong>,</p>

        <p><strong>{{ $subscription->donor->name }}</strong> has cancelled their recurring subscription for <strong>{{ $subscription->campaign->title }}</strong>.</p>

        <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
            <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Subscriber</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{ $subscription->donor->name }}</td></tr>
            <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Amount</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; font-weight: 600;">{{ $subscription->currency_symbol }} {{ number_format($subscription->amount, 2) }}</td></tr>
            <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Campaign</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{ $subscription->campaign->title }}</td></tr>
            <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Total Payments</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{ $subscription->payment_count ?? 0 }}</td></tr>
            <tr><td style="padding: 8px; color: #64748b;">Cancelled At</td><td style="padding: 8px;">{{ $subscription->cancelled_at ? myrTime($subscription->cancelled_at) : myrTime(now()) }}</td></tr>
        </table>

        <p style="font-size: 0.875rem; color: #94a3b8;">
            You are receiving this because your organisation has subscription cancellation notifications enabled.
        </p>
        @include('emails.partials.admin-footer', ['organization' => $subscription->campaign->organization])

    </div>
</body>
</html>
