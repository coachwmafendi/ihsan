<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    @include('emails.partials.admin-styles')
</head>
<body style="font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; color: #1a1a2e; background-color: #f8fafc; margin: 0; padding: 0;">

    <div style="max-width: 600px; margin: 0 auto; padding: 24px 16px;">

        {{-- Header --}}
        <div style="margin-bottom: 24px;">
            <h1 style="margin: 0 0 4px; font-size: 20px; font-weight: 700; color: #0f766e;">Weekly Donation Report</h1>
            <p class="email-small" style="margin: 0; font-size: 13px; color: #64748b;">{{ $period }} &middot; {{ $organization->name }}</p>
        </div>

        {{-- Summary row --}}
        <div style="background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 24px; overflow: hidden; display: flex; flex-wrap: wrap;">
            <div style="flex: 1; padding: 20px; min-width: 140px; box-sizing: border-box; border-bottom: 1px solid #e2e8f0;">
                <div class="email-label" style="font-size: 13px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Total Donations</div>
                <div class="email-metric" style="font-size: 20px; font-weight: 700; color: #0f766e;">{{ $donationCount }}</div>
            </div>
            <div style="flex: 1; padding: 20px; min-width: 140px; box-sizing: border-box;">
                <div class="email-label" style="font-size: 13px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Total Amount</div>
                <div class="email-metric" style="font-size: 20px; font-weight: 700; color: #0f766e;">{{ $hasApproximation ? "≈ MYR" : "MYR" }} {{ $totalAmount }}</div>
                @if ($previousTotalAmount !== null)
                    <div class="email-small" style="margin-top: 4px; font-size: 13px; color: #64748b;">
                        @if ($changePercent === null)
                            Nothing raised the week before.
                        @else
                            <span style="color: {{ $changePercent >= 0 ? '#15803d' : '#b91c1c' }}; font-weight: 600;">{{ $changePercent >= 0 ? '▲' : '▼' }} {{ rtrim(rtrim(number_format(abs($changePercent), 1), '0'), '.') }}%</span> from the week before (MYR {{ $previousTotalAmount }})
                        @endif
                    </div>
                @endif
            </div>
        </div>

        {{-- Campaigns --}}
        @if (count($campaigns) > 0)
            <div style="margin-bottom: 8px;">
                <h2 style="margin: 0 0 4px; font-size: 15px; font-weight: 700; color: #1a1a2e;">Campaigns</h2>
                <p class="email-small" style="margin: 0 0 12px; font-size: 13px; color: #64748b;">Your active campaigns, plus any campaign that received donations this week.</p>

                @foreach ($campaigns as $campaign)
                    <div style="background: {{ $loop->even ? '#f8fafc' : '#ffffff' }}; border-radius: 8px; padding: 14px 16px; margin-bottom: 1px; border-bottom: 1px solid #e2e8f0; word-wrap: break-word;">
                        <div class="email-value" style="font-size: 15px; font-weight: 500; color: #1a1a2e; margin-bottom: 6px; line-height: 1.4;">{{ $campaign['title'] }}</div>
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse;">
                            <tr>
                                <td class="email-small" style="font-size: 13px; color: #64748b; white-space: nowrap;">{{ $campaign['count'] }} {{ Str::plural('donation', $campaign['count']) }}</td>
                                <td class="email-value" align="right" style="font-size: 15px; font-weight: 600; color: #0f766e; text-align: right; padding-left: 12px; white-space: nowrap;">{{ ($campaign['approximate'] ?? $hasApproximation) ? "≈ MYR" : "MYR" }} {{ $campaign['total'] }}</td>
                            </tr>
                        </table>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Monthly plans --}}
        @isset($plans['new_plan_count'])
            <div style="margin: 24px 0 8px;">
                <h2 style="margin: 0 0 12px; font-size: 15px; font-weight: 700; color: #1a1a2e;">Monthly plans</h2>
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; border-collapse: separate; border-spacing: 0; overflow: hidden;">
                    @foreach ([
                        'Started this week' => $plans['new_plan_count'],
                        'Cancelled this week' => $plans['cancelled_plan_count'],
                        'Past due now' => $plans['past_due_plan_count'],
                    ] as $planLabel => $planCount)
                        @php($rowBorder = $loop->last ? '' : ' border-bottom: 1px solid #e2e8f0;')
                        <tr>
                            <td class="email-small" style="padding: 12px 16px; font-size: 14px; color: #64748b;{{ $rowBorder }}">{{ $planLabel }}</td>
                            <td class="email-value" align="right" style="padding: 12px 16px; font-size: 15px; font-weight: 600; text-align: right; color: {{ $loop->last && $planCount > 0 ? '#b45309' : '#1a1a2e' }};{{ $rowBorder }}">{{ $planCount }}</td>
                        </tr>
                    @endforeach
                </table>
                @if ($plans['past_due_plan_count'] > 0)
                    <p class="email-small" style="margin: 8px 0 0; font-size: 13px; color: #64748b;">Past due plans are retried automatically. A plan that keeps failing usually needs the supporter to update their card.</p>
                @endif
            </div>
        @endisset

        @if ($reportUrl)
            <p style="margin: 24px 0;">
                <a class="email-button" href="{{ $reportUrl }}" style="display: inline-block; background-color: #0f766e; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 6px; font-weight: 600;">View this week's report</a>
            </p>
        @endif

        {{-- Footer --}}
        <div style="margin-top: 32px; padding-top: 20px; border-top: 1px solid #e2e8f0;">
            @if ($hasApproximation)
                <p class="email-small" style="margin: 0 0 4px; font-size: 12px; color: #94a3b8;">
                    ≈ Totals include donations taken in other currencies, converted to MYR at each donation's settled exchange rate.
                </p>
            @endif
            <p class="email-small" style="margin: 0 0 4px; font-size: 12px; color: #94a3b8;">
                You are receiving this because your organization has weekly donation summary enabled.
            </p>
            @include('emails.partials.admin-footer', ['organization' => $organization])
        </div>

    </div>

</body>
</html>
