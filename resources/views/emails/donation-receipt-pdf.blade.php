<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: 'Manrope', 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #1a1a2e;
            margin: 0;
            padding: 0;
        }
        .header {
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 2px solid #0d9488;
            margin-bottom: 24px;
        }
        .header h1 {
            color: #0d9488;
            font-size: 20px;
            margin: 0 0 4px;
        }
        .header p {
            color: #64748b;
            font-size: 11px;
            margin: 0;
        }
        .org-name {
            font-size: 13px;
            font-weight: 600;
            color: #334155;
            margin-top: 4px;
        }
        .greeting {
            font-size: 13px;
            margin-bottom: 16px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 16px 0;
        }
        th, td {
            padding: 8px 10px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
            font-size: 12px;
        }
        th {
            background: #f1f5f9;
            font-weight: 600;
            color: #334155;
        }
        .label {
            color: #64748b;
            width: 30%;
        }
        .value {
            font-weight: 600;
        }
        .amount {
            font-size: 18px;
            font-weight: 700;
            color: #0d9488;
        }
        .footer {
            margin-top: 24px;
            padding-top: 16px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            font-size: 10px;
            color: #94a3b8;
        }
        .badge {
            display: inline-block;
            padding: 2px 10px;
            background: #dcfce7;
            color: #16a34a;
            border-radius: 10px;
            font-size: 11px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Donation Receipt</h1>
        <div class="org-name">{{ $donation->campaign->organization->name }}</div>
        <p>Receipt #{{ $donation->id }} &middot; {{ $donation->created_at->format('d M Y') }}</p>
    </div>

    <p class="greeting">Dear <strong>{{ $donation->donor->name }}</strong>,</p>
    <p>Thank you for your generous donation. Your contribution has been received successfully.</p>

    <table>
        <tr>
            <td class="label">Amount</td>
            <td class="value amount">RM {{ number_format($donation->gross_amount, 2) }}</td>
        </tr>
        <tr>
            <td class="label">Campaign</td>
            <td class="value">{{ $donation->campaign->title }}</td>
        </tr>
        <tr>
            <td class="label">Organization</td>
            <td class="value">{{ $donation->campaign->organization->name }}</td>
        </tr>
        <tr>
            <td class="label">Donation Type</td>
            <td class="value">{{ $donation->type === \App\Enums\DonationType::Recurring ? 'Recurring' : 'One-time' }}</td>
        </tr>
        <tr>
            <td class="label">Date</td>
            <td class="value">{{ $donation->created_at->format('d M Y, h:i A') }}</td>
        </tr>
        <tr>
            <td class="label">Payment Method</td>
            <td class="value">{{ $donation->payment_method_brand ?? 'Card' }}</td>
        </tr>
        <tr>
            <td class="label">Status</td>
            <td class="value"><span class="badge">Successful</span></td>
        </tr>
    </table>

    @if ($donation->type === \App\Enums\DonationType::Recurring)
        <p style="font-size: 11px; color: #64748b; font-style: italic;">
            This is a recurring donation. You will receive a receipt for each successful payment.
        </p>
    @endif

    <div class="footer">
        <p>{{ config('app.name') }} &mdash; Tax-exempt receipts are available upon request from the organization.</p>
        <p>If you have any questions, please contact the organization directly.</p>
    </div>
</body>
</html>
