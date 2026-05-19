<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="font-family: Manrope, sans-serif; line-height: 1.6; color: #1a1a2e;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #0f766e;">Thank you for your donation!</h1>

        <p>Hi <strong>{{ $donation->donor->name }}</strong>,</p>

        <p>Your donation of <strong>RM {{ number_format($donation->gross_amount, 2) }}</strong> to <strong>{{ $donation->campaign->title }}</strong> has been received successfully.</p>

        @if ($donation->type === \App\Enums\DonationType::Recurring)
            <p><em>This is a recurring donation. You will receive a receipt for each successful payment.</em></p>
        @endif

        <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
            <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Amount</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; font-weight: 600;">RM {{ number_format($donation->gross_amount, 2) }}</td></tr>
            <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Campaign</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{ $donation->campaign->title }}</td></tr>
            <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Organization</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{ $donation->campaign->organization->name }}</td></tr>
            <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Date</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{ $donation->created_at->format('d M Y, h:i A') }}</td></tr>
            <tr><td style="padding: 8px; color: #64748b;">Status</td><td style="padding: 8px; color: #16a34a; font-weight: 600;">Successful</td></tr>
        </table>

        <p>Thank you for your support!</p>
    </div>
</body>
</html>
