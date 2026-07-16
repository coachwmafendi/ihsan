<!DOCTYPE html>
<html>
<head><meta charset="utf-8">@include('emails.partials.admin-styles')</head>
<body style="font-family: 'Plus Jakarta Sans', sans-serif; line-height: 1.6; color: #1a1a2e;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        @php
            $hasCoveredFee = $donation->donor_fee_covered > 0;
            $netAmount = $donation->formatted_net_amount;
            $isRecurring = $donation->type === \App\Enums\DonationType::Recurring;
            $subscription = $donation->subscription;
        @endphp

        @if ($isRecurring)
            @php
                $paymentNumber = $subscription?->payment_count ?? 1;
                $ordinalSuffix = match ($paymentNumber % 100) {
                    11, 12, 13 => 'th',
                    default => match ($paymentNumber % 10) {
                        1 => 'st',
                        2 => 'nd',
                        3 => 'rd',
                        default => 'th',
                    },
                };
                $ordinal = $paymentNumber.$ordinalSuffix;

                $amountWithConversionOriginalFirst = $donation->display_payment_amount;

                $intervalLabel = match ($subscription?->interval->value ?? 'monthly') {
                    'monthly' => 'month',
                    'weekly' => 'week',
                    'yearly' => 'year',
                    default => $subscription?->interval->value ?? 'month',
                };
            @endphp

            <p>Hi <strong>{{ $donation->campaign->organization->name }}</strong>,</p>

            <p>You've received your <strong>{{ $ordinal }}</strong> recurring donation of <strong>{{ $amountWithConversionOriginalFirst }}</strong> from <strong>{{ $donation->donor->name }}</strong> for <strong>{{ $donation->campaign->title }}</strong>.</p>

            <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
                <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Supporter</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{ $donation->donor->name }}</td></tr>
                <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Supporter ID</td><td class="email-id" style="padding: 8px; border-bottom: 1px solid #e2e8f0; font-family: monospace; font-size: 13px;">{{ $donation->donor->public_id }}</td></tr>
                <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Email</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{ $donation->donor->email }}</td></tr>
                <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Donation ID</td><td class="email-id" style="padding: 8px; border-bottom: 1px solid #e2e8f0; font-family: monospace; font-size: 13px;">{{ $donation->public_id }}</td></tr>
                <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">{{ $hasCoveredFee ? 'Donation (incl. fee)' : 'Donation' }}</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; font-weight: 600;">{{ $donation->total_charged_with_conversion }}</td></tr>
                @if ($hasCoveredFee)
                    <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Fee Covered by Supporter</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">{{ $donation->display_fee_covered }}</td></tr>
                @endif
                <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Net to Organization</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; font-weight: 600; color: #16a34a;">{{ $netAmount }}</td></tr>
                <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Campaign</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{ $donation->campaign->title }}</td></tr>
                <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Date</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{ myrTime($donation->created_at) }}</td></tr>
                @if ($subscription !== null && $subscription->current_period_end !== null)
                    <tr><td style="padding: 8px; color: #64748b;">Next Billing Date</td><td style="padding: 8px;">{{ myrTime($subscription->current_period_end, withLabel: false, format: 'd M Y') }}</td></tr>
                @endif
            </table>

            <p style="margin: 24px 0;">
                <a href="{{ appPanelRoute('app.donations.show', $donation) }}" class="email-button" style="display: inline-block; background-color: #16a34a; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 6px; font-weight: 600;">View in {{ config('app.name') }}</a>
            </p>
        @else
            <h1 style="color: #16a34a;">New Donation Received 🎉</h1>

            <p>Hi <strong>{{ $donation->campaign->organization->name }}</strong>,</p>

            <p>You have received a new donation of <strong>{{ $donation->total_charged_with_conversion }}</strong> for <strong>{{ $donation->campaign->title }}</strong>.</p>

            <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
                <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Supporter</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{ $donation->donor->name }}</td></tr>
                <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Supporter ID</td><td class="email-id" style="padding: 8px; border-bottom: 1px solid #e2e8f0; font-family: monospace; font-size: 13px;">{{ $donation->donor->public_id }}</td></tr>
                <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Email</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{ $donation->donor->email }}</td></tr>
                <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Donation ID</td><td class="email-id" style="padding: 8px; border-bottom: 1px solid #e2e8f0; font-family: monospace; font-size: 13px;">{{ $donation->public_id }}</td></tr>
                <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">{{ $hasCoveredFee ? 'Donation (incl. fee)' : 'Donation' }}</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; font-weight: 600;">{{ $donation->total_charged_with_conversion }}</td></tr>
                @if ($hasCoveredFee)
                    <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Fee Covered by Supporter</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">{{ $donation->display_fee_covered }}</td></tr>
                @endif
                <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Net to Organization</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; font-weight: 600; color: #16a34a;">{{ $netAmount }}</td></tr>
                <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Campaign</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{ $donation->campaign->title }}</td></tr>
                <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Type</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{ ucwords(str_replace('_', ' ', $donation->type->value)) }}</td></tr>
                <tr><td style="padding: 8px; color: #64748b;">Date</td><td style="padding: 8px;">{{ myrTime($donation->created_at) }}</td></tr>
            </table>

            <p style="margin: 24px 0;">
                <a href="{{ appPanelRoute('app.donations.show', $donation) }}" class="email-button" style="display: inline-block; background-color: #16a34a; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 6px; font-weight: 600;">View in {{ config('app.name') }}</a>
            </p>
        @endif

        <p class="email-small" style="font-size: 0.875rem; color: #94a3b8;">
            You are receiving this because your organization has new donation notifications enabled.
        </p>
        @include('emails.partials.admin-footer', ['organization' => $donation->campaign->organization])

    </div>
</body>
</html>
