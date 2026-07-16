<!DOCTYPE html>
<html>
<head><meta charset="utf-8">@include('emails.partials.admin-styles')</head>
<body style="font-family: 'Plus Jakarta Sans', sans-serif; line-height: 1.6; color: #1a1a2e;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <p>Hi <strong>{{ $organization->name }}</strong>,</p>

        <p>We recently detected a new login to your {{ config('app.name') }} account.</p>

        <p>To help keep your account secure, we’re notifying you of this activity for your awareness and peace of mind.</p>

        <p>If you recognize the login details below, no further action is required:</p>

        <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Location</td>
                <td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{ $country }} — {{ $ipAddress }}</td>
            </tr>
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Browser</td>
                <td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{ $browser }}</td>
            </tr>
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Date/Time</td>
                <td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{ myrTime($loggedInAt) }}</td>
            </tr>
        </table>

        <p>If you do not recognize this activity or did not initiate this login, we strongly recommend that you <a href="{{ appPanelRoute('app.settings.account') }}" target="_blank" rel="noopener noreferrer" style="color: #2563eb; text-decoration: underline;">reset your password immediately and review your account</a> for any unauthorized changes.</p>

        <p>Thank you for helping us keep your account secure.</p>

        <p>Regards,<br>The {{ config('app.name') }} Team</p>

        @include('emails.partials.admin-support-footer', ['emailReference' => $emailReference])
    </div>
</body>
</html>
