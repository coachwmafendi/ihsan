@extends('emails.layouts.donor', ['organization' => $subscription->campaign->organization, 'locale' => $locale])

@php
    $t = fn (string $key, array $replace = []) => trans($key, $replace, $locale);
@endphp

@section('preheader', $t('emails.donor_subscription_cancelled.preheader', [
    'campaign' => $subscription->campaign->title,
    'organization' => $subscription->campaign->organization->name,
]))

@section('title', $t('emails.donor_subscription_cancelled.title'))

@section('content')
    <h1 style="font-size: 24px; color: #f59e0b;">{{ $t('emails.donor_subscription_cancelled.title') }}</h1>

    <p style="font-size: 18px;">{{ $t('emails.common.greeting', ['name' => $donor->name]) }},</p>

    <p style="font-size: 18px;">
        {{ $t('emails.donor_subscription_cancelled.intro', [
            'campaign' => $subscription->campaign->title,
            'organization' => $subscription->campaign->organization->name,
        ]) }}
    </p>

    <p style="font-size: 18px;">{{ $t('emails.donor_subscription_cancelled.body') }}</p>

    <table style="width: 100%; border-collapse: collapse; margin: 24px 0;">
        <tr>
            <td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b; width: 40%;">{{ $t('emails.donor_subscription_cancelled.campaign_label') }}</td>
            <td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{ $subscription->campaign->title }}</td>
        </tr>
        <tr>
            <td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">{{ $t('emails.donor_subscription_cancelled.amount_label') }}</td>
            <td style="padding: 8px; border-bottom: 1px solid #e2e8f0; font-weight: 600;">{{ $subscription->currency_symbol }} {{ number_format((float) $subscription->amount, 2) }}</td>
        </tr>
        <tr>
            <td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">{{ $t('emails.donor_subscription_cancelled.frequency_label') }}</td>
            <td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{ ucfirst($subscription->interval->value) }}</td>
        </tr>
        <tr>
            <td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">{{ $t('emails.donor_subscription_cancelled.total_payments_label') }}</td>
            <td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{ $subscription->payment_count ?? 0 }}</td>
        </tr>
        <tr>
            <td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">{{ $t('emails.donor_subscription_cancelled.subscription_id_label') }}</td>
            <td style="padding: 8px; border-bottom: 1px solid #e2e8f0; font-family: monospace; font-size: 13px;">{{ $subscription->public_id }}</td>
        </tr>
        <tr>
            <td style="padding: 8px; color: #64748b;">{{ $t('emails.donor_subscription_cancelled.cancelled_at_label') }}</td>
            <td style="padding: 8px;">{{ $subscription->cancelled_at ? myrTime($subscription->cancelled_at) : myrTime(now()) }}</td>
        </tr>
    </table>

    <p style="font-size: 18px;">– {{ $t('emails.donor_subscription_cancelled.sign_off', ['organization' => $subscription->campaign->organization->name]) }}</p>

    <p style="font-size: 16px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 16px; margin-top: 28px;">
        {{ $t('emails.donor_subscription_cancelled.reason') }}
    </p>

    <p style="font-size: 16px; color: #94a3b8;">
        <a href="{{ route('donorportal.dashboard', $subscription->campaign->organization) }}" style="color: #0d9488; text-decoration: underline;">{{ $t('emails.receipt.donor_portal_cta') }}</a>
        {{ $t('emails.receipt.donor_portal_text') }}
    </p>
@endsection
