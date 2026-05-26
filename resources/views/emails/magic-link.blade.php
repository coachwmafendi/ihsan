<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="font-family: 'Plus Jakarta Sans', sans-serif; line-height: 1.6; color: #1a1a2e;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #0f766e;">Your Donation Portal</h1>

        <p>Hi <strong>{{ $donor->name }}</strong>,</p>

        <p>Click the button below to access your donation portal where you can view your donation history and manage subscriptions.</p>

        <a href="{{ route('donorportal.magic-login', ['token' => $token]) }}"
           style="display: inline-block; padding: 12px 24px; background-color: #0f766e; color: white; text-decoration: none; border-radius: 6px; font-weight: 600; margin: 20px 0;">
            Access Donation Portal
        </a>

        <p style="color: #94a3b8; font-size: 14px;">This link expires in 24 hours. If you did not request this, please ignore this email.</p>
    </div>
</body>
</html>
