@component('mail::message')
# Checkout Problem Reported

Someone trying to donate to **{{ $campaign->title }}** told us the checkout did not work for them. They were not signed in, so there is no name and no address to reply to.

## What they said

{{ $reportMessage }}

## Where it happened

{{-- Not a table: two columns headed "Field" and "Value" say nothing that the
     labels beside the values do not already say. --}}
**Campaign:** {{ $campaign->title }}

**Organization:** {{ $organization->name }}

**Page:** {{ $pageUrl ?: 'Not recorded' }}

**Device:** {{ $deviceType ?: 'Not recorded' }}

**Received:** {{ myrTime(now()) }}

Ihsan support has a copy of this already. If it points at the payment step rather than your own page, we are looking at it — there is nothing you need to forward.

@endcomponent
