@extends('emails.layouts.donor', ['organization' => $campaign->organization, 'locale' => $locale])

@php
    $t = fn (string $key, array $replace = []) => trans($key, $replace, $locale);
@endphp

@section('preheader', $t('emails.campaign_completed.preheader', [
    'campaign' => $campaign->title,
    'organization' => $campaign->organization->name,
    'amount' => 'MYR '.number_format((float) $campaign->target_amount, 2),
]))

@section('title', $t('emails.campaign_completed.title'))

@section('content')
    <h1 style="color: #16a34a;">{{ $t('emails.campaign_completed.title') }}</h1>

    <p>{{ $t('emails.common.greeting', ['name' => $donor->name]) }},</p>

    <p>
        {{ $t('emails.campaign_completed.intro', [
            'campaign' => $campaign->title,
            'organization' => $campaign->organization->name,
            'amount' => 'MYR '.number_format((float) $campaign->target_amount, 2),
        ]) }}
    </p>

    <p>{{ $t('emails.campaign_completed.thanks') }}</p>

    <p style="font-size: 0.875rem; color: #94a3b8;">
        {{ $t('emails.campaign_completed.reason') }}
    </p>
@endsection
