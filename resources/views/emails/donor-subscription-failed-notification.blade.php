@extends('emails.layouts.donor', ['organization' => $subscription->campaign->organization, 'locale' => $locale])

@php
    $t = fn (string $key, array $replace = []) => trans($key, $replace, $locale);
@endphp

@section('preheader', $t('emails.donor_subscription_failed.preheader', [
    'campaign' => $subscription->campaign->title,
]))

@section('title', $t('emails.donor_subscription_failed.title'))

@section('content')
    <h1 style="font-size: 28px; color: #0f766e; margin: 0 0 16px;">
        {{ $t('emails.donor_subscription_failed.title') }}
    </h1>

    <p style="font-size: 18px;">{{ $t('emails.common.greeting', ['name' => $donor->name]) }},</p>

    <p style="font-size: 18px;">
        {{ $t('emails.donor_subscription_failed.intro', [
            'campaign' => $subscription->campaign->title,
        ]) }}
    </p>

    <p style="font-size: 18px;">{{ $t('emails.donor_subscription_failed.body') }}</p>

    <table style="width: 100%; border-collapse: collapse; margin: 24px 0;">
        <tr>
            <td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b; width: 40%;">
                {{ $t('emails.donor_subscription_failed.campaign_label') }}
            </td>
            <td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{ $subscription->campaign->title }}</td>
        </tr>
        <tr>
            <td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">
                {{ $t('emails.donor_subscription_failed.amount_label') }}
            </td>
            <td style="padding: 8px; border-bottom: 1px solid #e2e8f0; font-weight: 600;">{{ $subscription->displayAmount() }}</td>
        </tr>
        <tr>
            <td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">
                {{ $t('emails.donor_subscription_failed.frequency_label') }}
            </td>
            <td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{ ucfirst($subscription->interval->value) }}</td>
        </tr>
        <tr>
            <td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">
                {{ $t('emails.donor_subscription_failed.total_payments_label') }}
            </td>
            <td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{ $subscription->payment_count ?? 0 }}</td>
        </tr>
        <tr>
            <td style="padding: 8px; color: #64748b;">
                {{ $t('emails.donor_subscription_failed.failed_at_label') }}
            </td>
            <td style="padding: 8px;">{{ myrTime($subscription->updated_at) }}</td>
        </tr>
    </table>

    @if ($campaignUrl)
        <p style="text-align: center; margin: 28px 0;">
            <a href="{{ $campaignUrl }}" style="display: inline-block; background-color: #228B22; color: #ffffff; text-decoration: none; padding: 14px 28px; border-radius: 6px; font-size: 18px; font-weight: 600;">
                {{ $t('emails.donor_subscription_failed.cta') }}
            </a>
        </p>
    @endif

    <p style="font-size: 18px;">– {{ $t('emails.donor_subscription_failed.sign_off', ['organization' => $subscription->campaign->organization->name]) }}</p>

    <p style="font-size: 16px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 16px; margin-top: 28px;">
        {{ $t('emails.donor_subscription_failed.reason') }}
    </p>
@endsection
