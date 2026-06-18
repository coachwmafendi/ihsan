<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="font-family: 'Plus Jakarta Sans', sans-serif; line-height: 1.6; color: #1a1a2e;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        @php
            $symbol = $donation->currency_symbol;
            $hasCoveredFee = $donation->donor_fee_covered > 0;
            $netAmount = \App\Support\Currency::symbol('myr').' '.number_format((float) $donation->net_amount, 2);
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

                $originalAmountDisplay = $symbol.' '.number_format((float) $donation->total_charged, 2);
                $conversionAppend = '';

                if (strtolower($donation->currency) !== 'myr' && $donation->base_amount !== null) {
                    $exchangeRate = (float) ($donation->exchange_rate ?? 0);
                    $feeInBase = $exchangeRate > 0
                        ? round((float) $donation->donor_fee_covered * $exchangeRate, 2)
                        : (float) $donation->donor_fee_covered;
                    $conversionAppend = ' (≈MYR '.number_format((float) $donation->base_amount + $feeInBase, 2).')';
                }

                $amountWithConversionOriginalFirst = $originalAmountDisplay.$conversionAppend;

                $intervalLabel = match ($subscription?->interval->value ?? 'monthly') {
                    'monthly' => 'month',
                    'weekly' => 'week',
                    'yearly' => 'year',
                    default => $subscription?->interval->value ?? 'month',
                };
            @endphp

            <h1 style="color: #16a34a; font-size: 24px; line-height: 1.3;">{{ $ordinal }} recurring {{ $amountWithConversionOriginalFirst }} donation by {{ $donation->donor->name }} on {{ $donation->campaign->title }}</h1>

            <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
                <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Supporter</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{ $donation->donor->name }}</td></tr>
                <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Email</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{ $donation->donor->email }}</td></tr>
                <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Donation ID</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; font-family: monospace; font-size: 13px;">{{ $donation->public_id }}</td></tr>
                <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">{{ $hasCoveredFee ? 'Donation (incl. fee)' : 'Donation' }}</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; font-weight: 600;">{{ $donation->total_charged_with_conversion }}</td></tr>
                @if ($hasCoveredFee)
                    <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Fee Covered by Donor</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">{{ $symbol }} {{ number_format((float) $donation->donor_fee_covered, 2) }}</td></tr>
                @endif
                <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Net to Organisation</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; font-weight: 600; color: #16a34a;">{{ $netAmount }}</td></tr>
                <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Campaign</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{ $donation->campaign->title }}</td></tr>
                <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Date</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{ $donation->created_at->format('d M Y, h:i A') }}</td></tr>
                @if ($subscription !== null && $subscription->current_period_end !== null)
                    <tr><td style="padding: 8px; color: #64748b;">Next Billing Date</td><td style="padding: 8px;">{{ $subscription->current_period_end->format('d M Y') }}</td></tr>
                @endif
            </table>

            <p style="margin: 24px 0;">
                <a href="{{ route('app.donations.show', $donation) }}" style="display: inline-block; background-color: #16a34a; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 6px; font-weight: 600;">View in Ihsan</a>
            </p>
        @else
            <h1 style="color: #16a34a;">New Donation Received 🎉</h1>

            <p>Hi <strong>{{ $donation->campaign->organization->name }}</strong>,</p>

            <p>You have received a new donation of <strong>{{ $donation->total_charged_with_conversion }}</strong> for <strong>{{ $donation->campaign->title }}</strong>.</p>

            <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
                <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Supporter</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{ $donation->donor->name }}</td></tr>
                <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Email</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{ $donation->donor->email }}</td></tr>
                <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Donation ID</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; font-family: monospace; font-size: 13px;">{{ $donation->public_id }}</td></tr>
                <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">{{ $hasCoveredFee ? 'Donation (incl. fee)' : 'Donation' }}</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; font-weight: 600;">{{ $donation->total_charged_with_conversion }}</td></tr>
                @if ($hasCoveredFee)
                    <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Fee Covered by Donor</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">{{ $symbol }} {{ number_format((float) $donation->donor_fee_covered, 2) }}</td></tr>
                @endif
                <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Net to Organisation</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; font-weight: 600; color: #16a34a;">{{ $netAmount }}</td></tr>
                <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Campaign</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{ $donation->campaign->title }}</td></tr>
                <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Type</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{ ucwords(str_replace('_', ' ', $donation->type->value)) }}</td></tr>
                <tr><td style="padding: 8px; color: #64748b;">Date</td><td style="padding: 8px;">{{ $donation->created_at->format('d M Y, h:i A') }}</td></tr>
            </table>

            <p style="margin: 24px 0;">
                <a href="{{ route('app.donations.show', $donation) }}" style="display: inline-block; background-color: #16a34a; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 6px; font-weight: 600;">View in Ihsan</a>
            </p>
        @endif

        <p style="font-size: 0.875rem; color: #94a3b8;">
            You are receiving this because your organisation has new donation notifications enabled.
        </p>
        @include('emails.partials.admin-footer', ['organization' => $donation->campaign->organization])

    </div>
</body>
</html>
