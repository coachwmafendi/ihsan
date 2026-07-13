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
| **Time** | {{ myrTime($donation->created_at, format: 'M j, Y H:i:s') }} |

@if ($action === 'blocked')
**This donation has been blocked and will not be processed.**
@elseif ($action === 'flagged')
**This donation was flagged for review but has been processed.**
@endif

@component('mail::button', ['url' => $url])
View Fraud Dashboard
@endcomponent

If you have a question, contact us at [{{ support_email() }}](mailto:{{ support_email() }}) and include email reference "{{ $donation->campaign?->organization?->public_id ?? '' }}" in your message. To manage your email settings <a href="{{ route('app.settings.notifications') }}" target="_blank" rel="noopener noreferrer">update your notification settings</a>.

Sent with ❤️ from {{ config('app.name') }}
@endcomponent
