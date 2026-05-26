<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', 'DejaVu Sans', sans-serif;
            font-size: 11px;
            line-height: 1.5;
            color: #1a1a2e;
            margin: 0;
            padding: 0;
        }
        .header {
            text-align: center;
            padding-bottom: 16px;
            border-bottom: 2px solid #0d9488;
            margin-bottom: 24px;
        }
        .header h1 {
            color: #0d9488;
            font-size: 18px;
            margin: 0 0 2px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .org-name {
            font-size: 14px;
            font-weight: 700;
            color: #1e293b;
            margin-top: 2px;
        }
        .receipt-number {
            font-size: 10px;
            color: #64748b;
            margin-top: 2px;
        }
        .info-row {
            margin-bottom: 4px;
            font-size: 11px;
        }
        .info-row strong {
            display: inline-block;
            min-width: 100px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 8px 10px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
            font-size: 11px;
        }
        th {
            background: #f8fafc;
            font-weight: 600;
            color: #475569;
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: 0.5px;
        }
        .amount {
            font-size: 16px;
            font-weight: 700;
            color: #0d9488;
        }
        .badge {
            display: inline-block;
            padding: 2px 8px;
            background: #dcfce7;
            color: #16a34a;
            border-radius: 8px;
            font-size: 10px;
            font-weight: 600;
        }
        .footer {
            margin-top: 32px;
            padding-top: 12px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            font-size: 9px;
            color: #94a3b8;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Donation Receipt</h1>
        <div class="org-name">{{ $donation->campaign->organization->name }}</div>
        <div class="receipt-number">{{ $donation->invoice_number }} &middot; {{ $donation->created_at->format('d M Y') }}</div>
    </div>

    <div class="info-row"><strong>Donor</strong> {{ $donation->donor->name }}</div>
    <div class="info-row"><strong>Email</strong> {{ $donation->donor->email }}</div>
    <div class="info-row"><strong>Date</strong> {{ $donation->created_at->format('d M Y, h:i A') }}</div>

    <table>
        <tr>
            <th>Description</th>
            <th>Details</th>
        </tr>
        <tr>
            <td>Amount</td>
            <td class="amount">{{ $donation->currency_symbol }} {{ number_format($donation->gross_amount, 2) }}</td>
        </tr>
        <tr>
            <td>Campaign</td>
            <td>{{ $donation->campaign->title }}</td>
        </tr>
        <tr>
            <td>Type</td>
            <td>{{ $donation->type === \App\Enums\DonationType::Recurring ? 'Recurring' : 'One-time' }}</td>
        </tr>
        <tr>
            <td>Payment Method</td>
            <td>{{ $donation->payment_method_brand ? ucfirst($donation->payment_method_brand) : 'Card' }}</td>
        </tr>
        <tr>
            <td>Status</td>
            <td><span class="badge">Paid</span></td>
        </tr>
    </table>

    @if ($donation->type === \App\Enums\DonationType::Recurring)
        <p style="font-size: 10px; color: #64748b;">This is a recurring donation. A receipt is issued for each successful payment.</p>
    @endif

    <div class="footer">
        <p>{{ config('app.name') }} &mdash; Tax-exempt receipts available upon request.</p>
        <p>For inquiries, contact {{ $donation->campaign->organization->name }}.</p>
    </div>
</body>
</html>
