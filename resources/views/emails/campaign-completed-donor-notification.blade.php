<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="font-family: 'Plus Jakarta Sans', sans-serif; line-height: 1.6; color: #1a1a2e;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #16a34a;">🎉 Target Tercapai!</h1>

        <p>Hi <strong>{{ $donor->name }}</strong>,</p>

        <p>
            Terima kasih atas sokongan anda! Kempen <strong>{{ $campaign->title }}</strong>
            oleh <strong>{{ $campaign->organization->name }}</strong> telah berjaya mencapai target
            <strong>RM {{ number_format((float) $campaign->target_amount, 2) }}</strong>.
        </p>

        <p>Setiap derma anda telah membantu menjayakan kempen ini. Kami amat menghargai sumbangan anda.</p>

        <p style="font-size: 0.875rem; color: #94a3b8;">
            You are receiving this because you donated to this campaign.
        </p>
    </div>
</body>
</html>
