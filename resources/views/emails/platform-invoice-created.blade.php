<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="font-family: 'Plus Jakarta Sans', sans-serif; line-height: 1.6; color: #1a1a2e;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #0f766e;">Processing Fee Invoice</h1>

        <p>Hi <strong>{{ $invoice->organization->name }}</strong>,</p>

        <p>A new processing fee invoice has been generated for <strong>{{ \Carbon\Carbon::parse($invoice->period)->format('F Y') }}</strong>.</p>

        <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
            <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Invoice</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; font-weight: 600;">{{ $invoice->invoice_number }}</td></tr>
            <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Period</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{ \Carbon\Carbon::parse($invoice->period)->format('F Y') }}</td></tr>
            <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Total Fees</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; font-weight: 600;">RM {{ number_format($invoice->total_fees, 2) }}</td></tr>
            <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Due Date</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">14 days from today</td></tr>
            <tr><td style="padding: 8px; color: #64748b;">Status</td><td style="padding: 8px; color: #d97706; font-weight: 600;">Pending</td></tr>
        </table>

        @if ($invoice->stripe_invoice_url)
            <p>
                <a href="{{ $invoice->stripe_invoice_url }}" style="display: inline-block; padding: 12px 24px; background-color: #0f766e; color: #ffffff; text-decoration: none; border-radius: 8px; font-weight: 600;">
                    View Invoice on Stripe
                </a>
            </p>
        @endif

        <p style="margin-top: 20px; font-size: 0.875rem; color: #94a3b8;">
            This invoice was sent automatically by {{ config('app.name') }}.
        </p>
    </div>
</body>
</html>
