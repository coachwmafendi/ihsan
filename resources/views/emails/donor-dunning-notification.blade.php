@extends('emails.layouts.donor', ['organization' => $subscription->campaign->organization, 'locale' => $locale])

@php
    $t = fn (string $key, array $replace = []) => trans($key, $replace, $locale);
@endphp

@section('preheader', $t(match (true) {
    $isFinalAttempt => 'emails.dunning.preheader_final',
    $retryCount >= 3 => 'emails.dunning.preheader_almost_final',
    default => 'emails.dunning.preheader_default',
}, ['campaign' => $subscription->campaign->title]))

@section('content')
    @if ($isFinalAttempt)
        <h1 style="color: #dc2626;">{{ $t('emails.dunning.heading_final') }}</h1>
    @elseif ($retryCount >= 3)
        <h1 style="color: #ea580c;">{{ $t('emails.dunning.heading_almost_final') }}</h1>
    @else
        <h1 style="color: #dc2626;">{{ $t('emails.dunning.heading_default') }}</h1>
    @endif

    <p>{{ $t('emails.common.greeting', ['name' => $donor->name]) }},</p>

    @if ($isFinalAttempt)
        <p>{{ $t('emails.dunning.intro_final', ['campaign' => $subscription->campaign->title]) }}</p>
    @elseif ($retryCount >= 3)
        <p>{{ $t('emails.dunning.intro_almost_final', ['campaign' => $subscription->campaign->title]) }}</p>
    @else
        <p>{{ $t('emails.dunning.intro_default', ['campaign' => $subscription->campaign->title]) }}</p>
    @endif

    <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
        <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">{{ $t('emails.dunning.campaign') }}</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{ $subscription->campaign->title }}</td></tr>
        <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">{{ $t('emails.dunning.amount') }}</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; font-weight: 600;">{{ $subscription->displayAmount() }}</td></tr>
        <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">{{ $t('emails.dunning.frequency') }}</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{ ucfirst($subscription->interval->value) }}</td></tr>
        <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">{{ $t('emails.dunning.attempt') }}</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{ $retryCount }}</td></tr>
    </table>

    <p>
        <a href="{{ $loginUrl ?? route('donorportal.login', $subscription->campaign->organization) }}"
           style="display: inline-block; background: #0d9488; color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 600;">
            {{ $t('emails.dunning.update_payment') }}
        </a>
    </p>

    <p style="margin-top: 16px;">{{ $t('emails.dunning.already_updated') }}</p>

    <p style="font-size: 0.875rem; color: #94a3b8; margin-top: 24px;">
        {{ $t('emails.dunning.attempt_note', ['retry' => $retryCount, 'total' => $isFinalAttempt ? $t('emails.dunning.final') : '4']) }}
    </p>
@endsection
