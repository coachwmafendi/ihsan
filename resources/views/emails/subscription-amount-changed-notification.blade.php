@extends('emails.layouts.donor', ['organization' => $subscription->campaign->organization, 'locale' => $locale])

@php
    $t = fn (string $key, array $replace = []) => trans($key, $replace, $locale);
    $intervalLabel = $t('emails.subscription_amount_changed.interval_'.$subscription->interval->value);
@endphp

@section('preheader', $t('emails.subscription_amount_changed.preheader', [
    'campaign' => $subscription->campaign->title,
    'amount' => $amountDisplay,
    'interval' => $intervalLabel,
]))

@section('title', $t('emails.subscription_amount_changed.title'))

@section('content')
    <h1 style="color: #2563eb;">{{ $t('emails.subscription_amount_changed.title') }}</h1>

    <p>
        @if ($isDonor)
            {{ $t('emails.common.greeting', ['name' => $donor->name]) }},
        @elseif (filled($admin?->name))
            {{ $t('emails.common.greeting', ['name' => $admin->name]) }},
        @else
            Hi team,
        @endif
    </p>

    <p>
        @if ($isDonor)
            {{ $t('emails.subscription_amount_changed.intro', [
                'campaign' => $subscription->campaign->title,
                'previous' => $subscription->currency_symbol.' '.number_format($previousAmount, 2),
                'amount' => $amountDisplay,
                'interval' => $intervalLabel,
            ]) }}
        @else
            {{ $t('emails.subscription_amount_changed.intro_admin', [
                'donor' => $donor->name,
                'campaign' => $subscription->campaign->title,
                'previous' => $subscription->currency_symbol.' '.number_format($previousAmount, 2),
                'amount' => $amountDisplay,
                'interval' => $intervalLabel,
            ]) }}
        @endif
    </p>

    <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
        <tr>
            <td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">{{ $t('emails.subscription_amount_changed.donor') }}</td>
            <td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{ $subscription->donor->name }}</td>
        </tr>
        <tr>
            <td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">{{ $t('emails.subscription_amount_changed.previous_amount') }}</td>
            <td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{ $subscription->currency_symbol }} {{ number_format($previousAmount, 2) }}</td>
        </tr>
        <tr>
            <td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">{{ $t('emails.subscription_amount_changed.new_amount') }}</td>
            <td style="padding: 8px; border-bottom: 1px solid #e2e8f0; font-weight: 600;">{{ $amountDisplay }}</td>
        </tr>
        <tr>
            <td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">{{ $t('emails.subscription_amount_changed.frequency') }}</td>
            <td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{ $t('emails.subscription_amount_changed.frequency_'.$subscription->interval->value) }}</td>
        </tr>
        <tr>
            <td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">{{ $t('emails.subscription_amount_changed.campaign') }}</td>
            <td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{ $subscription->campaign->title }}</td>
        </tr>
        <tr>
            <td style="padding: 8px; color: #64748b;">{{ $t('emails.subscription_amount_changed.date') }}</td>
            <td style="padding: 8px;">{{ myrTime(now()) }}</td>
        </tr>
    </table>

    <p style="font-size: 0.875rem; color: #94a3b8;">
        {{ $t('emails.subscription_amount_changed.reason') }}
    </p>

    @if ($isDonor)
        <p style="font-size: 0.875rem; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 16px; margin-top: 24px;">
            <a href="{{ route('donorportal.dashboard', $subscription->campaign->organization) }}" style="color: #0d9488; text-decoration: underline;">{{ $t('emails.subscription_amount_changed.donor_portal_cta') }}</a>
            {{ $t('emails.subscription_amount_changed.donor_portal_text') }}
        </p>
    @else
        @include('emails.partials.admin-support-footer', ['emailReference' => $emailReference])
    @endif
@endsection
