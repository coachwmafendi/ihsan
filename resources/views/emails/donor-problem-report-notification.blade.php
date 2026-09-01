@component('mail::message')
# Donor Problem Report

A donor reported a problem from the donor portal.

## Report

{{ $reportMessage }}

## Who sent it

| Field | Value |
|-------|-------|
| **Donor** | {{ $donor->name ?? 'Unknown' }} |
| **Email** | {{ $donor->email }} |
| **Organization** | {{ $organization->name }} |
| **Organization code** | {{ $organization->code }} |
| **Received** | {{ myrTime(now()) }} |

Replying to this email goes straight to the donor.

@endcomponent
