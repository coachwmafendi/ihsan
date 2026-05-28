<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="font-family: 'Plus Jakarta Sans', sans-serif; line-height: 1.6; color: #1a1a2e;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <div style="background: linear-gradient(135deg, #7c3aed, #a855f7); border-radius: 12px; padding: 24px; margin-bottom: 24px; text-align: center;">
            <h1 style="color: #fff; margin: 0 0 4px; font-size: 1.5rem;">Large Donation Received</h1>
            <p style="color: #ddd6fe; margin: 0; font-size: 0.875rem;">High-value donation alert</p>
        </div>

        <p>Hi <strong>{{ $donation->campaign->organization->name }}</strong>,</p>

        <p style="font-size: 1.125rem;">
            A donation worth <strong style="color: #7c3aed; font-size: 1.5rem;">{{ $amountDisplay }}</strong>
            has been received for <strong>{{ $donation->campaign->title }}</strong>.
        </p>

        <table style="width: 100%; border-collapse: collapse; margin: 24px 0; border-radius: 8px; overflow: hidden;">
            <tr style="background: #f5f3ff;">
                <td style="padding: 12px 16px; color: #6b7280; width: 120px;">Donor</td>
                <td style="padding: 12px 16px; font-weight: 600;">{{ $donation->donor->name }}</td>
            </tr>
            <tr style="background: #fff;">
                <td style="padding: 12px 16px; color: #6b7280;">Amount</td>
                <td style="padding: 12px 16px; font-weight: 700; font-size: 1.25rem; color: #7c3aed;">{{ $amountDisplay }}</td>
            </tr>
            <tr style="background: #f5f3ff;">
                <td style="padding: 12px 16px; color: #6b7280;">Campaign</td>
                <td style="padding: 12px 16px;">{{ $donation->campaign->title }}</td>
            </tr>
            <tr style="background: #fff;">
                <td style="padding: 12px 16px; color: #6b7280;">Date</td>
                <td style="padding: 12px 16px;">{{ $donation->created_at->format('d M Y, h:i A') }}</td>
            </tr>
        </table>

        <div style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 16px; border-radius: 8px; margin: 24px 0;">
            <p style="margin: 0; font-size: 0.875rem; color: #92400e;">
                <strong>Suggested action:</strong> Contact the donor personally to express your gratitude. Large donations like this open doors for long-term relationships.
            </p>
        </div>

        <p style="font-size: 0.875rem; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 16px;">
            You are receiving this because large donation notifications are enabled for your organisation. Manage your notification preferences in the control panel.
        </p>
    </div>
</body>
</html>
