@component('mail::message')
# Fraud Alert: {{ ucfirst($action) }} Donation

A donation has been **{{ $action }}** by the fraud prevention system.

## Donation Details

| Field | Value |
|-------|-------|
| **Donor** | {{ $donation->donor?->name ?? 'Unknown' }} |
| **Email** | {{ $donation->donor?->email ?? 'N/A' }} |
| **Amount** | {{ strtoupper($donation->currency) }} {{ number_format((float) $donation->gross_amount, 2) }} |
| **Campaign** | {{ $donation->campaign?->title ?? 'N/A' }} |
| **Reason** | {{ $reason }} |
| **Risk Score** | {{ $donation->risk_score ?? 'N/A' }} |
| **Time** | {{ $donation->created_at->format('M j, Y H:i:s') }} |

@if ($action === 'blocked')
**This donation has been blocked and will not be processed.**
@elseif ($action === 'flagged')
**This donation was flagged for review but has been processed.**
@endif

@component('mail::button', ['url' => $url])
View Fraud Dashboard
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
