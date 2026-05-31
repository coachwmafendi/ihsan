<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="font-family: 'Plus Jakarta Sans', sans-serif; line-height: 1.6; color: #1a1a2e; background-color: #f8fafc;">
    <div style="max-width: 600px; margin: 0 auto; padding: 40px 20px;">

        {{-- Header --}}
        <div style="margin-bottom: 32px;">
            <h1 style="margin: 0 0 4px; font-size: 22px; font-weight: 700; color: #0f766e;">Weekly Donation Report</h1>
            <p style="margin: 0; font-size: 14px; color: #64748b;">{{ $period }} &middot; {{ $organization->name }}</p>
        </div>

        {{-- Summary row --}}
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 32px; background: #ffffff; border-radius: 8px; overflow: hidden; border: 1px solid #e2e8f0;">
            <tr>
                <td style="padding: 20px 24px; border-right: 1px solid #e2e8f0;">
                    <div style="font-size: 12px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Total Donations</div>
                    <div style="font-size: 28px; font-weight: 700; color: #0f766e;">{{ $donationCount }}</div>
                </td>
                <td style="padding: 20px 24px;">
                    <div style="font-size: 12px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Total Amount</div>
                    <div style="font-size: 28px; font-weight: 700; color: #0f766e;">RM {{ $totalAmount }}</div>
                </td>
            </tr>
        </table>

        {{-- Campaigns --}}
        @if (count($campaigns) > 0)
            <div style="margin-bottom: 8px;">
                <h2 style="margin: 0 0 4px; font-size: 16px; font-weight: 700; color: #1a1a2e;">Campaigns</h2>
                <p style="margin: 0 0 16px; font-size: 14px; color: #64748b;">These campaigns received donations during this reporting period.</p>

                <table style="width: 100%; border-collapse: collapse;">
                    @foreach ($campaigns as $campaign)
                        <tr style="background: {{ $loop->even ? '#f8fafc' : '#ffffff' }}; border-radius: 6px;">
                            <td style="padding: 12px 16px; font-size: 14px; font-weight: 500; color: #1a1a2e; border-bottom: 1px solid #e2e8f0;">{{ $campaign['title'] }}</td>
                            <td style="padding: 12px 16px; font-size: 14px; color: #64748b; text-align: center; border-bottom: 1px solid #e2e8f0; white-space: nowrap;">{{ $campaign['count'] }} {{ Str::plural('donation', $campaign['count']) }}</td>
                            <td style="padding: 12px 16px; font-size: 14px; font-weight: 600; color: #0f766e; text-align: right; border-bottom: 1px solid #e2e8f0; white-space: nowrap;">RM {{ $campaign['total'] }}</td>
                        </tr>
                    @endforeach
                </table>
            </div>
        @endif

        {{-- Footer --}}
        <p style="margin-top: 40px; font-size: 12px; color: #94a3b8;">
            You are receiving this because your organisation has weekly donation summary enabled.
            To change your notification preferences, visit your <a href="{{ config('app.url') }}/app/pemberitahuan" style="color: #0f766e;">notification settings</a>.
        </p>
        <p style="margin-top: 8px; font-size: 12px; color: #94a3b8;">Sent with ❤️ from {{ config('app.name') }}</p>

    </div>
</body>
</html>
