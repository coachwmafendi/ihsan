<!DOCTYPE html>
<html>
<head><meta charset="utf-8">@include('emails.partials.admin-styles')</head>
<body style="font-family: 'Plus Jakarta Sans', sans-serif; line-height: 1.6; color: #1a1a2e;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #16a34a;">New Recurring Subscription 🎉</h1>

        <p>Hi <strong>{{ $donation->campaign->organization->name }}</strong>,</p>

        <p><strong>{{ $donation->donor->name }}</strong> has started a recurring subscription of <strong>{{ $amountDisplay }}</strong> for <strong>{{ $donation->campaign->title }}</strong>.</p>

        @php
            $symbol = $donation->currency_symbol;
            $hasCoveredFee = $donation->donor_fee_covered > 0;
            $netAmount = $donation->formatted_net_amount;
        @endphp

        <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
            <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Supporter</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{ $donation->donor->name }}</td></tr>
            <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Supporter ID</td><td class="email-id" style="padding: 8px; border-bottom: 1px solid #e2e8f0; font-family: monospace; font-size: 13px;">{{ $donation->donor->public_id }}</td></tr>
            <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Email</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{ $donation->donor->email }}</td></tr>
            <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">{{ $hasCoveredFee ? 'Donation (incl. fee)' : 'Donation' }}</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; font-weight: 600;">{{ $amountDisplay }}</td></tr>
            @if ($hasCoveredFee)
                <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Fee Covered by Supporter</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">{{ $symbol }} {{ number_format((float) $donation->donor_fee_covered, 2) }}</td></tr>
            @endif
            <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Net to Organization</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; font-weight: 600; color: #16a34a;">{{ $netAmount }}</td></tr>
            <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Frequency</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">Monthly</td></tr>
            <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Campaign</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{ $donation->campaign->title }}</td></tr>
            <tr><td style="padding: 8px; color: #64748b;">Date</td><td style="padding: 8px;">{{ myrTime($donation->created_at) }}</td></tr>
        </table>

        <p style="margin: 24px 0; text-align: center;">
            <a class="email-button" href="{{ $donation->subscription ? appPanelRoute('app.subscriptions.show', $donation->subscription) : appPanelRoute('app.donations.show', $donation) }}" style="display: inline-block; background-color: #16a34a; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 6px; font-weight: 600;">View in {{ config('app.name') }}</a>
        </p>

        <p class="email-small" style="font-size: 0.875rem; color: #94a3b8;">
            You are receiving this because your organization has new subscription notifications enabled.
        </p>
        @include('emails.partials.admin-footer', ['organization' => $donation->campaign->organization])

    </div>
</body>
</html>
