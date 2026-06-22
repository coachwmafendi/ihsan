@extends('emails.layouts.donor', ['organization' => $donation->campaign->organization, 'locale' => $locale])

@php
    $t = fn (string $key, array $replace = []) => trans($key, $replace, $locale);
@endphp

@section('preheader', $t('emails.donor_new_subscription.preheader', [
    'campaign' => $donation->campaign->title,
    'organization' => $donation->campaign->organization->name,
]))

@section('title', $t('emails.donor_new_subscription.title'))

@section('content')
    <h1 style="font-size: 24px; color: #0f766e;">{{ $t('emails.donor_new_subscription.title') }}</h1>

    <p style="font-size: 18px;">{{ $t('emails.common.greeting', ['name' => $donor->name]) }},</p>

    <p style="font-size: 18px;">
        {{ $t('emails.donor_new_subscription.intro', [
            'organization' => $donation->campaign->organization->name,
        ]) }}
    </p>

    <p style="font-size: 18px;">{{ $t('emails.donor_new_subscription.body') }}</p>

    <p style="font-size: 18px;">– {{ $t('emails.donor_new_subscription.sign_off', ['organization' => $donation->campaign->organization->name]) }}</p>

    @if ($downloadUrl)
        <p style="text-align: center; margin: 28px 0;">
            <a href="{{ $downloadUrl }}" style="display: inline-flex; align-items: center; gap: 10px; background-color: #0f766e; color: #ffffff; text-decoration: none; padding: 14px 28px; border-radius: 6px; font-size: 18px; font-weight: 600;">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: inline-block; vertical-align: middle;">
                    <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/>
                    <polyline points="14 2 14 8 20 8"/>
                    <line x1="16" y1="13" x2="8" y2="13"/>
                    <line x1="16" y1="17" x2="8" y2="17"/>
                    <line x1="10" y1="9" x2="8" y2="9"/>
                </svg>
                {{ $t('emails.receipt.download_receipt') }}
            </a>
        </p>
    @endif

    <p style="font-size: 16px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 16px; margin-top: 28px;">
        <a href="{{ route('donorportal.dashboard', $donation->campaign->organization) }}" style="color: #0d9488; text-decoration: underline;">{{ $t('emails.receipt.donor_portal_cta') }}</a>
        {{ $t('emails.receipt.donor_portal_text') }}
    </p>
@endsection
