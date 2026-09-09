@component('mail::message')
# Your donation did not go through

{{ $donorName ? 'Hi '.$donorName.',' : 'Hello,' }}

Your {{ $amount }} donation to **{{ $campaignTitle }}** was declined by your bank, so nothing was charged and no money left your account.

Here is what they told us:

> {{ $reason->message }}

@if ($reason->advice())
{{ $reason->advice() }}
@endif

@component('mail::button', ['url' => $retryUrl])
{{ $reason->worthRetrying() ? 'Try again' : 'Give with another card' }}
@endcomponent

The amount you chose is already filled in, so it is a couple of taps.

If you have changed your mind, you can ignore this - we will not write to you about it again.

{{ $organizationName }}
@endcomponent
