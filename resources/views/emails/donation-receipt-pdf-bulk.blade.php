<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 0; }
        body {
            font-family: 'Plus Jakarta Sans', 'DejaVu Sans', sans-serif;
            font-size: 11px;
            line-height: 1.5;
            color: #1a1a2e;
            margin: 0;
            padding: 0;
        }
        .receipt-page {
            padding: 40px;
            page-break-after: always;
        }
        .receipt-page:last-child {
            page-break-after: auto;
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
            margin-top: 4px;
        }
        .receipt-number {
            font-size: 10px;
            color: #64748b;
            margin-top: 4px;
        }
        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        .info-col {
            display: table-cell;
            width: 48%;
            vertical-align: top;
        }
        .info-label {
            font-size: 9px;
            text-transform: uppercase;
            color: #94a3b8;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .info-value {
            font-size: 11px;
            color: #1e293b;
            font-weight: 500;
        }
        table.details {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table.details th {
            background: #f8fafc;
            padding: 8px 10px;
            text-align: left;
            font-size: 9px;
            text-transform: uppercase;
            color: #64748b;
            border-bottom: 2px solid #e2e8f0;
        }
        table.details td {
            padding: 10px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 11px;
        }
        .amount {
            font-size: 16px;
            font-weight: 700;
            color: #0d9488;
        }
        .footer {
            margin-top: 24px;
            padding-top: 16px;
            border-top: 1px solid #f1f5f9;
            font-size: 9px;
            color: #94a3b8;
            text-align: center;
        }
    </style>
</head>
<body>
@foreach ($donations as $donation)
    <div class="receipt-page">
        <div class="header">
            <h1>Donation Receipt</h1>
            <div class="org-name">{{ $donation->campaign->organization->name }}</div>
            <div class="receipt-number">{{ $donation->invoice_number }} · {{ myrTime($donation->created_at, withLabel: false, format: 'd M Y') }}</div>
        </div>

        <div class="info-grid">
            <div class="info-col">
                <div class="info-label">Donor</div>
                <div class="info-value">{{ $donation->donor->name }}</div>
                <div class="info-value" style="font-size: 10px; color: #64748b;">{{ $donation->donor->email }}</div>
            </div>
            <div class="info-col" style="text-align: right;">
                <div class="info-label">Campaign</div>
                <div class="info-value">{{ $donation->campaign->title }}</div>
            </div>
        </div>

        <table class="details">
            <thead>
                <tr>
                    <th>Description</th>
                    <th style="text-align: right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        Donation to {{ $donation->campaign->title }}<br>
                        <span style="font-size: 10px; color: #64748b;">{{ $donation->type->value === 'recurring' ? 'Monthly donation' : 'One-time donation' }} · {{ strtoupper($donation->payment_method_brand ?? 'Card') }}</span>
                    </td>
                    <td style="text-align: right;">
                        <div class="amount">{{ $donation->formatted_amount }}</div>
                        @if ($donation->currency !== 'myr' && $donation->base_amount !== null)
                            <div style="font-size: 10px; color: #64748b; margin-top: 2px;">≈ MYR {{ number_format($donation->base_amount, 2) }}</div>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td style="color: #64748b;">Payment method</td>
                    <td style="text-align: right; color: #64748b;">{{ strtoupper($donation->payment_method_brand ?? 'Card') }}</td>
                </tr>
                <tr>
                    <td style="color: #64748b;">Status</td>
                    <td style="text-align: right; color: #0d9488; font-weight: 600;">Succeeded</td>
                </tr>
                @if ($donation->donor_message)
                    <tr>
                        <td colspan="2" style="color: #64748b; font-style: italic;">"{{ $donation->donor_message }}"</td>
                    </tr>
                @endif
            </tbody>
        </table>

        <div class="footer">
            <p style="font-size: 10px; color: #64748b;">This is a recurring donation. A receipt is issued for each successful payment.</p>
            <p>{{ config('app.name') }} — Tax-exempt receipts available upon request.</p>
        </div>
    </div>
@endforeach
</body>
</html>
