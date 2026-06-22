<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="font-family: 'Plus Jakarta Sans', sans-serif; line-height: 1.6; color: #1a1a2e;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #dc2626;">Donation Refunded</h1>

        <p>Hi <strong>{{ $donation->campaign->organization->name }}</strong>,</p>

        <p>A donation of <strong>{{ $amountDisplay }}</strong> for <strong>{{ $donation->campaign->title }}</strong> has been refunded.</p>

        <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
            <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Donor</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{ $donation->donor->name }}</td></tr>
            <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Amount Refunded</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; font-weight: 600; color: #dc2626;">{{ $amountDisplay }}</td></tr>
            <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Campaign</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{ $donation->campaign->title }}</td></tr>
            <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Original Donation</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">#{{ $donation->id }}</td></tr>
            <tr><td style="padding: 8px; color: #64748b;">Date</td><td style="padding: 8px;">{{ myrTime($donation->updated_at) }}</td></tr>
        </table>

        <p style="margin: 24px 0;">
            <a href="{{ route('app.donations.show', $donation) }}" style="display: inline-block; background-color: #16a34a; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 6px; font-weight: 600;">View in {{ config('app.name') }}</a>
        </p>

        <p style="font-size: 0.875rem; color: #94a3b8;">
            You are receiving this because your organisation has refund notifications enabled.
        </p>
        @include('emails.partials.admin-footer', ['organization' => $donation->campaign->organization])

    </div>
</body>
</html>
