@component('mail::message')
# New Organization Registered

**{{ $organization->name }}** has registered and is waiting for approval.

| Field | Value |
|-------|-------|
| **Organization** | {{ $organization->name }} |
| **Registration** | {{ $organization->registration_type }} — {{ $organization->ros_rob_number }} |
| **Sector** | {{ $organization->sector }} |
| **Contact Email** | {{ $organization->contact_email }} |
| **Website** | {{ $organization->website_url }} |
@if ($organization->facebook_url)
| **Facebook** | {{ $organization->facebook_url }} |
@endif
| **Registered** | {{ myrTime($organization->created_at, format: 'M j, Y H:i') }} |

Nothing is live for them until the organization is approved.

@component('mail::button', ['url' => $url])
Review Organization
@endcomponent

Sent with ❤️ from {{ config('app.name') }}
@endcomponent
