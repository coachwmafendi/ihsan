<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="font-family: 'Plus Jakarta Sans', sans-serif; line-height: 1.6; color: #1a1a2e;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #16a34a;">🎉 Target Reached!</h1>

        <p>Hi <strong>{{ $donor->name }}</strong>,</p>

        <p>
            Thank you for your support! The campaign <strong>{{ $campaign->title }}</strong>
            by <strong>{{ $campaign->organization->name }}</strong> has successfully reached its target of
            <strong>RM {{ number_format((float) $campaign->target_amount, 2) }}</strong>.
        </p>

        <p>Every donation you made helped make this campaign a success. We truly appreciate your contribution.</p>

        <p style="font-size: 0.875rem; color: #94a3b8;">
            You are receiving this because you donated to this campaign.
        </p>

        @include('emails.partials.org-footer', ['organization' => $campaign->organization])
    </div>
</body>
</html>
