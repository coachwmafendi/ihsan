<!DOCTYPE html>
<html>
<head><meta charset="utf-8">@include('emails.partials.admin-styles')</head>
<body style="font-family: 'Plus Jakarta Sans', sans-serif; line-height: 1.6; color: #1a1a2e;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #0f766e;">Campaign Milestone Reached!</h1>

        <p>Hi <strong>{{ $campaign->organization->name }}</strong>,</p>

        <p>Your campaign <strong>{{ $campaign->title }}</strong> has reached a milestone!</p>

        <div style="background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 24px; margin: 20px 0; text-align: center;">
            <div style="font-size: 48px; font-weight: 800; color: #16a34a;">{{ $milestone }}%</div>
            <div class="email-small" style="font-size: 14px; color: #64748b; margin-top: 4px;">of target reached</div>
        </div>

        <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
            <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Campaign</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; font-weight: 600;">{{ $campaign->title }}</td></tr>
            <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Progress</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; font-weight: 600;">{{ $percent }}%</td></tr>
            <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Collected</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; font-weight: 600;">{{ $currency }} {{ $collected }}</td></tr>
            <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Target</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; font-weight: 600;">{{ $currency }} {{ $target }}</td></tr>
            <tr><td style="padding: 8px; color: #64748b;">Status</td><td style="padding: 8px; color: #16a34a; font-weight: 600;">{{ $percent >= 100 ? 'Target Achieved!' : 'On Track' }}</td></tr>
        </table>

        <p style="margin: 24px 0;">
            <a class="email-button" href="{{ appPanelRoute('app.campaigns.edit', $campaign) }}" style="display: inline-block; background-color: #16a34a; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 6px; font-weight: 600;">View in {{ config('app.name') }}</a>
        </p>

        <p class="email-small" style="font-size: 0.875rem; color: #94a3b8;">
            You are receiving this because your organization has campaign milestone notifications enabled.
        </p>
        @include('emails.partials.admin-footer', ['organization' => $campaign->organization])

    </div>
</body>
</html>
