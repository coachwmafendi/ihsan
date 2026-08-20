<!DOCTYPE html>
<html>
<head><meta charset="utf-8">@include('emails.partials.admin-styles')</head>
<body style="font-family: 'Plus Jakarta Sans', sans-serif; line-height: 1.6; color: #1a1a2e;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #0f766e;">Payment Method Updated</h1>

        <p>Hi <strong>{{ $subscription->campaign->organization->name }}</strong>,</p>

        <p><strong>{{ $subscription->donor->name }}</strong> has updated the payment method for their recurring donation for <strong>{{ $subscription->campaign->title }}</strong>.</p>

        <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
            @if ($previousPaymentMethod)
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Previous card</td>
                    <td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{ ucfirst($previousPaymentMethod->brand).' ****'.$previousPaymentMethod->last4 }}</td>
                </tr>
            @endif
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">New card</td>
                <td style="padding: 8px; border-bottom: 1px solid #e2e8f0; font-weight: 600;">{{ ucfirst($newPaymentMethod->brand).' ****'.$newPaymentMethod->last4 }}</td>
            </tr>
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Supporter</td>
                <td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{ $subscription->donor->name }} ({{ $subscription->donor->email }})</td>
            </tr>
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Campaign</td>
                <td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{ $subscription->campaign->title }}</td>
            </tr>
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Amount</td>
                <td style="padding: 8px; border-bottom: 1px solid #e2e8f0; font-weight: 600;">{{ $subscription->displayAmount() }}</td>
            </tr>
            <tr>
                <td style="padding: 8px; color: #64748b;">Frequency</td>
                <td style="padding: 8px;">{{ ucfirst($subscription->interval->value) }}</td>
            </tr>
        </table>

        <p style="margin: 24px 0; text-align: center;">
            <a class="email-button" href="{{ appPanelRoute('app.subscriptions.show', $subscription) }}" style="display: inline-block; background-color: #228B22; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 6px; font-weight: 600;">View in {{ config('app.name') }}</a>
        </p>

        @include('emails.partials.admin-footer', ['organization' => $subscription->campaign->organization])
    </div>
</body>
</html>
