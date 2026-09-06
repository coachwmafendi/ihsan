@component('mail::message')
# Fee drift detected

Over the last {{ $days }} day(s), the processor fee Stripe charged differs from the
rate the donation form quotes by more than {{ $threshold }} percentage points.

A positive drift means Stripe charged **more** than we quote, so donors covering
the transaction costs are covering too little and organizations receive less than
the donation intended. A negative drift means donors are being asked for too much.

@component('mail::table')
| Currency / card country | Donations | Drift |
| --- | ---: | ---: |
@foreach ($groups as $group => $detail)
| {{ $group }} | {{ $detail['count'] }} | {{ sprintf('%+.2f', $detail['drift']) }} points |
@endforeach
@endcomponent

Check Stripe's current pricing for the affected combinations, then update
`App\Services\DonationFeeEstimator`. Measure against settled balance
transactions rather than the published table where the two disagree.

@endcomponent
