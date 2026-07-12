<!DOCTYPE html>
<html>
<head><meta charset="utf-8">@include('emails.partials.admin-styles')</head>
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

        @if ($isFinalAttempt)
            <p>This was the final retry attempt. The subscription is now past due and no further automatic retries are scheduled.</p>
        @else
            <p>Stripe will automatically retry this payment. This is retry {{ $subscription->retry_count ?? 0 }}. Please monitor the situation.</p>
        @endif

        <p style="margin: 24px 0;">
            <a class="email-button" href="{{ route('app.subscriptions.show', $subscription) }}" style="display: inline-block; background-color: #16a34a; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 6px; font-weight: 600;">View in {{ config('app.name') }}</a>
        </p>

        <p class="email-small" style="font-size: 0.875rem; color: #94a3b8;">
            You are receiving this because your organization has failed payment notifications enabled.
        </p>
        @include('emails.partials.admin-footer', ['organization' => $subscription->campaign->organization])

    </div>
</body>
</html>
