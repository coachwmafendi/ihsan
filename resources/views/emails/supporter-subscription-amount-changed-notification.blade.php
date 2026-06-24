@extends('emails.layouts.donor', ['organization' => $subscription->campaign->organization, 'locale' => $locale])

@php
    $t = fn (string $key, array $replace = []) => trans($key, $replace, $locale);
    $intervalLabel = $t('emails.supporter_subscription_amount_changed.interval_'.$subscription->interval->value);
@endphp

@section('preheader', $t('emails.supporter_subscription_amount_changed.preheader', [
    'campaign' => $subscription->campaign->title,
    'amount' => $currentAmountDisplay,
    'interval' => $intervalLabel,
]))

@section('title', $t('emails.supporter_subscription_amount_changed.title'))

@section('content')
    <style>
        .chip-link { transition: transform 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease; }
        .chip-link:hover { transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.08); background-color: #f0fdfa !important; }
    </style>

    <h1 style="font-size: 28px; line-height: 1.3; color: #0f766e; margin: 0 0 16px;">{{ $t('emails.supporter_subscription_amount_changed.heading') }}</h1>

    <p style="font-size: 17px; line-height: 1.6;">{{ $t('emails.common.greeting', ['name' => $donor->name]) }},</p>

    <p style="font-size: 17px; line-height: 1.6;">
        {{ $t('emails.supporter_subscription_amount_changed.intro', [
            'organization' => $subscription->campaign->organization->name,
            'campaign' => $subscription->campaign->title,
            'previous' => $previousAmountDisplay,
            'amount' => $currentAmountDisplay,
            'interval' => $intervalLabel,
        ]) }}
    </p>

    <div style="margin: 28px 0; padding: 24px; border: 1px solid #e2e8f0; border-radius: 10px; background-color: #f0fdfa; text-align: center;">
        <p style="margin: 0 0 8px; font-size: 16px; color: #64748b;">
            {{ $currentAmountDisplay }} <span style="font-size: 14px;">/ {{ $intervalLabel }}</span>
        </p>
        <table role="presentation" border="0" cellpadding="0" cellspacing="0" style="margin: 0 auto; font-size: 15px; color: #334155;">
            <tr>
                <td style="padding: 4px 12px; color: #64748b; text-align: right;">{{ $t('emails.supporter_subscription_amount_changed.previous_label') }}</td>
                <td style="padding: 4px 12px; text-align: left; text-decoration: line-through;">{{ $previousAmountDisplay }}</td>
            </tr>
            <tr>
                <td style="padding: 4px 12px; color: #64748b; text-align: right;">{{ $t('emails.supporter_subscription_amount_changed.new_label') }}</td>
                <td style="padding: 4px 12px; text-align: left; font-weight: 600; color: #0f766e;">{{ $currentAmountDisplay }}</td>
            </tr>
        </table>
    </div>

    <p style="font-size: 17px; line-height: 1.6;">{{ $t('emails.supporter_subscription_amount_changed.body') }}</p>

    <p style="font-size: 17px; line-height: 1.6;">– {{ $t('emails.supporter_subscription_amount_changed.sign_off', ['organization' => $subscription->campaign->organization->name]) }}</p>

    @if ($upgradeChips)
        <div style="margin: 32px 0; padding: 20px; border: 1px solid #e2e8f0; border-radius: 10px; background-color: #ffffff; text-align: center;">
            <p style="margin: 0 0 16px; font-size: 17px; font-weight: 600; color: #334155;">
                {{ $t('emails.donor_recurring_payment.upgrade_heading', [
                    'amount' => $currentAmountDisplay,
                    'interval' => $intervalLabel,
                ]) }}
            </p>
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" style="margin: 0 auto;">
                <tr>
                    @foreach ($upgradeChips as $chip)
                        <td style="text-align: center; padding: 0 6px;">
                            <a href="{{ $chip['url'] }}" class="chip-link" style="display: inline-block; min-width: 90px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 12px 14px; text-decoration: none; font-size: 16px; font-weight: 600; color: #0f766e; background-color: #ffffff; transition: transform 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;">
                                {{ $chip['label'] }}
                            </a>
                        </td>
                    @endforeach
                </tr>
            </table>
        </div>
    @endif

    <p style="font-size: 16px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 16px; margin-top: 28px;">
        <a href="{{ route('donorportal.dashboard', $subscription->campaign->organization) }}" style="color: #0d9488; text-decoration: underline;">{{ $t('emails.receipt.donor_portal_cta') }}</a>
        {{ $t('emails.receipt.donor_portal_text') }}
    </p>
@endsection
