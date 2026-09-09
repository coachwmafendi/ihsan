@extends('emails.layouts.donor', ['organization' => $donation->campaign->organization, 'locale' => $locale])

@php
    $t = fn (string $key, array $replace = []) => trans($key, $replace, $locale);
@endphp

@section('preheader', $t('emails.failed_donation.preheader', ['campaign' => $donation->campaign->title]))

@section('title', $t('emails.failed_donation.title'))

@section('content')
    <h1 style="font-size: 24px; color: #0f766e;">{{ $t('emails.failed_donation.title') }}</h1>

    <p style="font-size: 18px;">{{ $t('emails.common.greeting', ['name' => $donor?->name]) }},</p>

    <p style="font-size: 18px;">
        {{ $t('emails.failed_donation.intro', [
            'amount' => $donation->display_donation_amount,
            'campaign' => $donation->campaign->title,
        ]) }}
    </p>

    <p style="font-size: 18px; margin-bottom: 8px;">{{ $t('emails.failed_donation.reason_label') }}</p>

    {{-- The bank's reason in our own words. Stripe's message is written for
         whoever is integrating: one decline reached a donor as "You can provide
         payment_method_data or a new PaymentMethod to attempt to fulfil this
         PaymentIntent again." --}}
    <p style="font-size: 18px; margin: 0 0 8px; padding: 12px 16px; background-color: #f8fafc; border-left: 3px solid #cbd5e1; color: #334155;">
        {{ $reason->donorMessage() }}
        @if ($reason->donorAdvice())
            {{ $reason->donorAdvice() }}
        @endif
    </p>

    <p style="text-align: center; margin: 28px 0;">
        <a href="{{ $retryUrl }}"
           style="display: inline-block; background-color: #228B22; color: #ffffff; padding: 14px 28px; border-radius: 6px; font-size: 18px; text-decoration: none; font-weight: 600;">
            {{ $reason->worthRetrying() ? $t('emails.failed_donation.retry') : $t('emails.failed_donation.another_card') }}
        </a>
    </p>

    <p style="font-size: 18px;">{{ $t('emails.failed_donation.prefilled') }}</p>

    <p style="font-size: 0.875rem; color: #64748b; margin-top: 24px;">{{ $t('emails.failed_donation.no_further_mail') }}</p>
@endsection
