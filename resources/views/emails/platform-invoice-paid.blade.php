<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="font-family: Manrope, sans-serif; line-height: 1.6; color: #1a1a2e;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #16a34a;">Payment Received</h1>

        <p>Hi <strong>{{ $invoice->organization->name }}</strong>,</p>

        <p>Your platform fee payment for <strong>{{ \Carbon\Carbon::parse($invoice->period)->format('F Y') }}</strong> has been received successfully.</p>

        <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
            <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Invoice</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; font-weight: 600;">{{ $invoice->invoice_number }}</td></tr>
            <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Period</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{ \Carbon\Carbon::parse($invoice->period)->format('F Y') }}</td></tr>
            <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Amount Paid</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; font-weight: 600;">RM {{ number_format($invoice->total_fees, 2) }}</td></tr>
            <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Paid At</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{ $invoice->paid_at?->format('d M Y, h:i A') ?? now()->format('d M Y, h:i A') }}</td></tr>
            <tr><td style="padding: 8px; color: #64748b;">Status</td><td style="padding: 8px; color: #16a34a; font-weight: 600;">Paid</td></tr>
        </table>

        @if ($invoice->stripe_invoice_pdf)
            <p>
                <a href="{{ $invoice->stripe_invoice_pdf }}" style="display: inline-block; padding: 12px 24px; background-color: #0f766e; color: #ffffff; text-decoration: none; border-radius: 8px; font-weight: 600;">
                    Download Invoice PDF (Stripe)
                </a>
            </p>
        @endif

        @if ($invoice->stripe_invoice_url)
            <p style="margin-top: 8px;">
                <a href="{{ $invoice->stripe_invoice_url }}" style="color: #0f766e;">View on Stripe</a>
            </p>
        @endif

        <p style="margin-top: 20px; font-size: 0.875rem; color: #94a3b8;">
            This receipt was sent automatically by {{ config('app.name') }}.
        </p>
    </div>
</body>
</html>
