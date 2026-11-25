@php
    $currency = $hasApproximation ? '≈ MYR' : 'MYR';
    $previousPeriod = \Illuminate\Support\Carbon::parse('1 '.$period)->subMonthNoOverflow()->format('F Y');
    $label = 'padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;';
    $value = 'padding: 8px; border-bottom: 1px solid #e2e8f0; font-weight: 600; text-align: right;';
    $heading = 'padding: 8px; text-align: left; color: #64748b; font-weight: 600;';
    $cell = 'padding: 8px; border-bottom: 1px solid #e2e8f0;';
@endphp
<!DOCTYPE html>
<html>
<head><meta charset="utf-8">@include('emails.partials.admin-styles')</head>
<body style="font-family: 'Plus Jakarta Sans', sans-serif; line-height: 1.6; color: #1a1a2e;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #0f766e; margin-bottom: 8px;">{{ $period }} report</h1>

        <p>Hi <strong>{{ $organization->name }}</strong>, here is how your fundraising went in <strong>{{ $period }}</strong>.</p>

        <table style="width: 100%; border-collapse: collapse; margin: 20px 0; background-color: #f0fdfa; border-radius: 8px;">
            <tr>
                <td style="padding: 20px;">
                    <div class="email-label" style="font-size: 13px; color: #64748b;">Total raised</div>
                    <div class="email-metric" style="font-size: 28px; font-weight: 700; color: #0f766e;">{{ $currency }} {{ $totalAmount }}</div>
                    <div class="email-small" style="font-size: 13px; color: #64748b;">
                        @if ($changePercent === null)
                            Nothing raised in {{ $previousPeriod }} to compare with.
                        @elseif ($changePercent >= 0)
                            <span style="color: #15803d; font-weight: 600;">▲ {{ rtrim(rtrim(number_format($changePercent, 1), '0'), '.') }}%</span> from {{ $previousPeriod }} (MYR {{ $previousTotalAmount }})
                        @else
                            <span style="color: #b91c1c; font-weight: 600;">▼ {{ rtrim(rtrim(number_format(abs($changePercent), 1), '0'), '.') }}%</span> from {{ $previousPeriod }} (MYR {{ $previousTotalAmount }})
                        @endif
                    </div>
                </td>
            </tr>
        </table>

        @if ($donationCount === 0)
            <p>No donations came in this month. Sharing a campaign link with your community is often the quickest way to restart giving.</p>
        @else
            <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
                <tr><td style="{{ $label }}">Donations</td><td style="{{ $value }}">{{ $donationCount }}</td></tr>
                @isset($highlights['average_amount'])
                    <tr><td style="{{ $label }}">Average gift</td><td style="{{ $value }}">{{ $currency }} {{ $highlights['average_amount'] }}</td></tr>
                    <tr><td style="{{ $label }}">Largest gift</td><td style="{{ $value }}">{{ $currency }} {{ $highlights['largest_amount'] }}</td></tr>
                @endisset
            </table>

            @isset($highlights['one_time_count'])
                <h3 style="color: #1a1a2e;">Type of giving</h3>
                <table style="width: 100%; border-collapse: collapse; margin: 12px 0 20px;">
                    <tr><td style="{{ $label }}">One-time · {{ $highlights['one_time_count'] }}</td><td style="{{ $value }}">{{ $currency }} {{ $highlights['one_time_total'] }}</td></tr>
                    <tr><td style="{{ $label }}">Monthly · {{ $highlights['recurring_count'] }}</td><td style="{{ $value }}">{{ $currency }} {{ $highlights['recurring_total'] }}</td></tr>
                </table>

                <h3 style="color: #1a1a2e;">Supporters</h3>
                <table style="width: 100%; border-collapse: collapse; margin: 12px 0 20px;">
                    <tr><td style="{{ $label }}">First-time supporters</td><td style="{{ $value }}">{{ $highlights['new_donor_count'] }}</td></tr>
                    <tr><td style="{{ $label }}">Returning supporters</td><td style="{{ $value }}">{{ $highlights['returning_donor_count'] }}</td></tr>
                </table>
            @endisset
        @endif

        @isset($highlights['new_plan_count'])
            <h3 style="color: #1a1a2e;">Monthly plans</h3>
            <table style="width: 100%; border-collapse: collapse; margin: 12px 0 20px;">
                <tr><td style="{{ $label }}">Started this month</td><td style="{{ $value }}">{{ $highlights['new_plan_count'] }}</td></tr>
                <tr><td style="{{ $label }}">Cancelled this month</td><td style="{{ $value }}">{{ $highlights['cancelled_plan_count'] }}</td></tr>
                <tr>
                    <td style="{{ $label }}">Past due now</td>
                    <td style="{{ $value }}{{ $highlights['past_due_plan_count'] > 0 ? ' color: #b45309;' : '' }}">{{ $highlights['past_due_plan_count'] }}</td>
                </tr>
            </table>
            @if ($highlights['past_due_plan_count'] > 0)
                <p class="email-small" style="margin-top: -8px; font-size: 13px; color: #64748b;">Past due plans are retried automatically. A plan that keeps failing usually needs the supporter to update their card.</p>
            @endif
        @endisset

        @if (count($campaigns) > 0)
            <h3 style="color: #1a1a2e;">By campaign</h3>
            <table style="width: 100%; border-collapse: collapse; margin: 12px 0 20px;">
                <tr style="background-color: #f1f5f9;">
                    <th style="{{ $heading }}">Campaign</th>
                    <th style="{{ $heading }}">Donations</th>
                    <th style="{{ $heading }} text-align: right;">Total</th>
                </tr>
                @foreach ($campaigns as $campaign)
                    <tr>
                        <td style="{{ $cell }}">{{ $campaign['title'] }}</td>
                        <td style="{{ $cell }}">{{ $campaign['count'] }}</td>
                        <td style="{{ $cell }} text-align: right; white-space: nowrap;">{{ ($campaign['approximate'] ?? $hasApproximation) ? '≈ MYR' : 'MYR' }} {{ $campaign['total'] }}</td>
                    </tr>
                @endforeach
            </table>
        @endif

        @if ($reportUrl)
            <p style="margin: 24px 0;">
                <a class="email-button" href="{{ $reportUrl }}" style="display: inline-block; background-color: #0f766e; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 6px; font-weight: 600;">View full report</a>
            </p>
        @endif

        @if ($hasApproximation)
            <p class="email-small" style="margin: 0 0 12px; font-size: 12px; color: #94a3b8;">
                ≈ Totals include donations taken in other currencies, converted to MYR at each donation's settled exchange rate.
            </p>
        @endif

        <p class="email-small" style="font-size: 0.875rem; color: #94a3b8;">
            You are receiving this because your organization has monthly report enabled.
        </p>
        @include('emails.partials.admin-footer', ['organization' => $organization])
    </div>
</body>
</html>
