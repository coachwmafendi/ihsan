<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="font-family: 'Plus Jakarta Sans', sans-serif; line-height: 1.6; color: #1a1a2e;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #0f766e;">Monthly Donation Report</h1>

        <p>Hi <strong>{{ $organization->name }}</strong>,</p>

        <p>Here is your donation summary for <strong>{{ $period }}</strong>.</p>

        <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
            <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Total Donations</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; font-weight: 600;">{{ $donationCount }}</td></tr>
            <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Total Amount</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; font-weight: 600;">RM {{ $totalAmount }}</td></tr>
        </table>

        @if (count($campaigns) > 0)
            <h3 style="color: #1a1a2e;">Per Campaign Breakdown</h3>
            <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
                <tr style="background-color: #f1f5f9;">
                    <th style="padding: 8px; text-align: left; color: #64748b; font-weight: 600;">Campaign</th>
                    <th style="padding: 8px; text-align: left; color: #64748b; font-weight: 600;">Donations</th>
                    <th style="padding: 8px; text-align: left; color: #64748b; font-weight: 600;">Total</th>
                </tr>
                @foreach ($campaigns as $campaign)
                    <tr>
                        <td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{ $campaign['title'] }}</td>
                        <td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{ $campaign['count'] }}</td>
                        <td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">RM {{ $campaign['total'] }}</td>
                    </tr>
                @endforeach
            </table>
        @endif

        <p style="font-size: 0.875rem; color: #94a3b8;">
            You are receiving this because your organisation has monthly report enabled.
        </p>
    </div>
</body>
</html>
