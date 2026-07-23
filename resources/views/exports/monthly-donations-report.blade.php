<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Report — {{ $organization->name }}</title>
    <style>
        @page { size: A4 portrait; margin: 40px; }
        body { font-family: Helvetica, Arial, sans-serif; font-size: 13px; color: #1f2937; margin: 0; padding: 0; }
        .page { padding: 40px; }
        h1 { font-size: 22px; margin: 0 0 6px; color: #111827; }
        .subtitle { font-size: 13px; color: #6b7280; margin-bottom: 20px; }
        .meta { color: #374151; margin-bottom: 24px; line-height: 1.6; }
        .meta strong { color: #111827; }
        .summary { width: 100%; margin-bottom: 30px; }
        .summary-box { width: 20%; padding: 12px; text-align: center; background: #f9fafb; border: 1px solid #e5e7eb; }
        .summary-label { font-size: 11px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; }
        .summary-value { font-size: 16px; font-weight: 700; color: #111827; }
        .summary-value.emerald { color: #047857; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { text-align: left; padding: 10px 12px; border-bottom: 1px solid #e5e7eb; }
        th { background: #f9fafb; font-weight: 600; font-size: 12px; color: #4b5563; text-transform: uppercase; }
        td { font-size: 13px; color: #374151; }
        .numeric { text-align: right; font-family: monospace; }
        .section-title { font-size: 15px; font-weight: 700; color: #111827; margin: 28px 0 8px; }
        .approx-note { font-size: 11px; color: #b45309; margin-top: 8px; }
        .footer { margin-top: 40px; padding-top: 12px; border-top: 1px solid #e5e7eb; font-size: 11px; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>
    <div class="page">
        <h1>Report</h1>
        <p class="subtitle">Donation collections by campaign</p>

        <div class="meta">
            <strong>{{ $organization->name }}</strong><br>
            Public ID: {{ $organization->public_id }}<br>
            Period: <strong>{{ $from->toDateString() }} to {{ $to->toDateString() }}</strong><br>
            Generated: {{ $generatedAt }}
        </div>

        @php($showDonorCovered = $summary['donor_covered_fees'] > 0)
        <div class="section-title">Summary</div>
        <table class="summary">
            <tr>
                <td class="summary-box" style="width: {{ $showDonorCovered ? '16.6%' : '20%' }};">
                    <div class="summary-label">Total Gross</div>
                    <div class="summary-value">MYR {{ number_format($summary['total_gross'], 2) }}</div>
                </td>
                @if ($showDonorCovered)
                    <td class="summary-box" style="width: 16.6%;">
                        <div class="summary-label">Donor-covered Fees</div>
                        <div class="summary-value">+MYR {{ number_format($summary['donor_covered_fees'], 2) }}</div>
                    </td>
                @endif
                <td class="summary-box" style="width: {{ $showDonorCovered ? '16.6%' : '20%' }};">
                    <div class="summary-label">Processing Fee</div>
                    <div class="summary-value">MYR {{ number_format($summary['processing_fee'], 2) }}</div>
                </td>
                <td class="summary-box">
                    <div class="summary-label">Net Received</div>
                    <div class="summary-value emerald">MYR {{ number_format($summary['net_received'], 2) }}</div>
                </td>
                <td class="summary-box">
                    <div class="summary-label">Total Donations</div>
                    <div class="summary-value">{{ number_format($summary['total_donations']) }}</div>
                </td>
                <td class="summary-box">
                    <div class="summary-label">Unique Donors</div>
                    <div class="summary-value">{{ number_format($summary['unique_donors']) }}</div>
                </td>
            </tr>
        </table>

        @if ($summary['has_approximations'])
            <p class="approx-note">* Non-MYR donations are converted using available exchange rates; totals may be approximate.</p>
        @endif

        <div class="section-title">Campaign Breakdown</div>
        <table>
            <thead>
                <tr>
                    <th>Campaign</th>
                    <th class="numeric">Donations</th>
                    <th class="numeric">Gross (MYR)</th>
                    @if ($showDonorCovered)
                        <th class="numeric">Donor-covered (MYR)</th>
                    @endif
                    <th class="numeric">Processing Fee (MYR)</th>
                    <th class="numeric">Net (MYR)</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($campaigns as $campaign)
                    <tr>
                        <td>{{ $campaign->title }}</td>
                        <td class="numeric">{{ number_format($campaign->donations_count) }}</td>
                        <td class="numeric">{{ number_format((float) $campaign->gross_amount, 2) }}</td>
                        @if ($showDonorCovered)
                            <td class="numeric">{{ number_format((float) $campaign->donor_covered_fees, 2) }}</td>
                        @endif
                        <td class="numeric">{{ number_format((float) $campaign->processing_fee, 2) }}</td>
                        <td class="numeric">{{ number_format((float) $campaign->net_amount, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $showDonorCovered ? 6 : 5 }}" style="text-align: center; color: #6b7280;">No donations found for the selected period.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="footer">
            Generated from Ihsan on {{ $generatedAt }}
        </div>
    </div>
</body>
</html>
