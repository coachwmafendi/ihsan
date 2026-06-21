@extends('emails.layouts.donor', ['organization' => $donation->campaign->organization, 'locale' => $locale])

@php
    $t = fn (string $key, array $replace = []) => trans($key, $replace, $locale);
@endphp

@section('preheader', $t('emails.donor_recurring_payment.preheader', [
    'campaign' => $donation->campaign->title,
    'organization' => $donation->campaign->organization->name,
]))

@section('title', $t('emails.donor_recurring_payment.title'))

@section('content')
    <h1 style="font-size: 32px; color: #0f766e;">{{ $t('emails.donor_recurring_payment.heading') }}</h1>

    <p style="font-size: 18px;">{{ $t('emails.common.greeting', ['name' => $donor->name]) }},</p>

    <p style="font-size: 18px;">
        {{ $t('emails.donor_recurring_payment.intro', [
            'organization' => $donation->campaign->organization->name,
        ]) }}
    </p>

    <p style="font-size: 18px;">{{ $t('emails.donor_recurring_payment.body') }}</p>

    <p style="font-size: 18px;">– {{ $t('emails.donor_recurring_payment.sign_off', ['organization' => $donation->campaign->organization->name]) }}</p>

    @if ($upgradeChips)
        <div style="margin: 32px 0; padding: 20px; border: 1px solid #e2e8f0; border-radius: 10px; background-color: #ffffff; text-align: center;">
            <p style="margin: 0 0 16px; font-size: 18px; font-weight: 600; color: #334155;">
                {{ $t('emails.donor_recurring_payment.upgrade_heading', [
                    'amount' => $currentAmountDisplay,
                    'interval' => $t('emails.donor_recurring_payment.upgrade_interval_monthly'),
                ]) }}
            </p>
            <div style="display: flex; gap: 10px; flex-wrap: wrap; justify-content: center;">
                @foreach ($upgradeChips as $chip)
                    <a href="{{ $chip['url'] }}" style="flex: 1 1 80px; max-width: 140px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 12px 8px; text-decoration: none; text-align: center; font-size: 16px; font-weight: 600; color: #0f766e; background-color: #ffffff;">
                        {{ $chip['label'] }}
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    @if ($downloadUrl)
        <p style="margin: 28px 0;">
            <a href="{{ $downloadUrl }}" style="display: inline-block; background-color: #0f766e; color: #ffffff; text-decoration: none; padding: 14px 28px; border-radius: 6px; font-size: 18px; font-weight: 600;">{{ $t('emails.receipt.download_receipt') }}</a>
        </p>
    @endif

    <p style="font-size: 16px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 16px; margin-top: 28px;">
        <a href="{{ route('donorportal.dashboard', $donation->campaign->organization) }}" style="color: #0d9488; text-decoration: underline;">{{ $t('emails.receipt.donor_portal_cta') }}</a>
        {{ $t('emails.receipt.donor_portal_text') }}
    </p>
@endsection
