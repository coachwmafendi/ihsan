@component('mail::message')
# Checkout Problem Reported

Someone trying to donate to **{{ $campaign->title }}** told us the checkout did not work for them. They were not signed in, so there is no name and no address to reply to.

## What they said

{{ $reportMessage }}

## Where it happened

| Field | Value |
|-------|-------|
| **Campaign** | {{ $campaign->title }} |
| **Organization** | {{ $organization->name }} |
| **Page** | {{ $pageUrl ?: 'Not recorded' }} |
| **Device** | {{ $deviceType ?: 'Not recorded' }} |
| **Received** | {{ myrTime(now()) }} |

If the report points at the payment step rather than your own page, forward it to Ihsan support and we will look at it.

@endcomponent
