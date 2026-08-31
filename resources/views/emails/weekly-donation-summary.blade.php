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
            </div>
        </div>

        {{-- Campaigns --}}
        @if (count($campaigns) > 0)
            <div style="margin-bottom: 8px;">
                <h2 style="margin: 0 0 4px; font-size: 15px; font-weight: 700; color: #1a1a2e;">Campaigns</h2>
                <p class="email-small" style="margin: 0 0 12px; font-size: 13px; color: #64748b;">These campaigns received donations during this reporting period.</p>

                @foreach ($campaigns as $campaign)
                    <div style="background: {{ $loop->even ? '#f8fafc' : '#ffffff' }}; border-radius: 8px; padding: 14px 16px; margin-bottom: 1px; border-bottom: 1px solid #e2e8f0; word-wrap: break-word;">
                        <div class="email-value" style="font-size: 15px; font-weight: 500; color: #1a1a2e; margin-bottom: 6px; line-height: 1.4;">{{ $campaign['title'] }}</div>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span class="email-small" style="font-size: 13px; color: #64748b; flex-shrink: 0;">{{ $campaign['count'] }} {{ Str::plural('donation', $campaign['count']) }}</span>
                            <span class="email-value" style="font-size: 15px; font-weight: 600; color: #0f766e; text-align: right; margin-left: 12px;">{{ $hasApproximation ? "≈ MYR" : "MYR" }} {{ $campaign['total'] }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Footer --}}
        <div style="margin-top: 32px; padding-top: 20px; border-top: 1px solid #e2e8f0;">
            @if ($hasApproximation)
                <p class="email-small" style="margin: 0 0 4px; font-size: 12px; color: #94a3b8;">
                    Totals include donations taken in other currencies, converted to MYR at each donation's settled exchange rate.
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
