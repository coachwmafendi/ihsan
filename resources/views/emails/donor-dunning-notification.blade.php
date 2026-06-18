<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="font-family: 'Plus Jakarta Sans', sans-serif; line-height: 1.6; color: #1a1a2e;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        @if ($isFinalAttempt)
            <h1 style="color: #dc2626;">Last Chance This Month</h1>
            <p>Hi <strong>{{ $subscription->donor->name }}</strong>,</p>
            <p>This is the <strong>final attempt</strong> to process your recurring donation for <strong>{{ $subscription->campaign->title }}</strong> this month.</p>
        @elseif ($retryCount >= 3)
            <h1 style="color: #ea580c;">Final Attempt Tomorrow</h1>
            <p>Hi <strong>{{ $subscription->donor->name }}</strong>,</p>
            <p>Tomorrow is the <strong>final attempt</strong> to process your recurring donation for <strong>{{ $subscription->campaign->title }}</strong>.</p>
        @else
            <h1 style="color: #dc2626;">Payment Failed</h1>
            <p>Hi <strong>{{ $subscription->donor->name }}</strong>,</p>
            <p>We couldn't process your recurring donation for <strong>{{ $subscription->campaign->title }}</strong>.</p>
        @endif

        <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
            <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Campaign</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{ $subscription->campaign->title }}</td></tr>
            <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Amount</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; font-weight: 600;">{{ $subscription->currency_symbol }} {{ number_format($subscription->amount, 2) }}</td></tr>
            <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Frequency</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{ ucfirst($subscription->interval->value) }}</td></tr>
            <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Attempt</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{ $retryCount }}</td></tr>
        </table>

        <p>
            <a href="{{ url('/donor-portal/' . $subscription->campaign->organization->code . '?token=' . $subscription->donor->magic_token) }}"
               style="display: inline-block; background: #0d9488; color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 600;">
                Update Payment Method
            </a>
        </p>

        <p style="margin-top: 16px;">If you've already updated your card, no action needed — Stripe will retry automatically.</p>

        <p style="font-size: 0.875rem; color: #94a3b8; margin-top: 24px;">
            This is attempt #{{ $retryCount }} of {{ $isFinalAttempt ? 'final' : '4' }} for this billing period.
        </p>

        @include('emails.partials.org-footer', ['organization' => $subscription->campaign->organization])
    </div>
</body>
</html>
