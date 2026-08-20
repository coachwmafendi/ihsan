@extends('emails.layouts.donor', ['organization' => $subscriptions->first()->campaign->organization, 'locale' => $locale])

@php
    $t = fn (string $key, array $replace = []) => trans($key, $replace, $locale);
    $subscriptionCount = $subscriptions->count();
    $expiryDate = $paymentMethod && $paymentMethod->expiresAt()
        ? $paymentMethod->expiresAt()->format('m/Y')
        : '';
@endphp

@section('preheader', $t('emails.donor_payment_method_expiring.preheader', [
    'last4' => $paymentMethod?->last4 ?? '',
    'days' => $daysRemaining,
    'expiry_date' => $expiryDate,
]))

@section('title', $t('emails.donor_payment_method_expiring.title'))

@section('content')
    <h1 style="font-size: 28px; color: #0f766e; margin: 0 0 16px;">
        {{ $t('emails.donor_payment_method_expiring.title') }}
    </h1>

    <p style="font-size: 18px;">{{ $t('emails.common.greeting', ['name' => $donor->name]) }},</p>

    <p style="font-size: 18px;">
        {{ $t('emails.donor_payment_method_expiring.intro', [
            'last4' => $paymentMethod?->last4 ?? '',
            'days' => $daysRemaining,
            'expiry_date' => $expiryDate,
        ]) }}
    </p>

    <p style="font-size: 18px;">{{ $t('emails.donor_payment_method_expiring.body') }}</p>

    <p style="font-size: 18px; margin-top: 24px;">
        {{ $subscriptionCount === 1
            ? $t('emails.donor_payment_method_expiring.subscription_count_singular', ['count' => $subscriptionCount])
            : $t('emails.donor_payment_method_expiring.subscription_count_plural', ['count' => $subscriptionCount]) }}
    </p>

    <table style="width: 100%; border-collapse: collapse; margin: 16px 0 24px;">
        <thead>
            <tr style="border-bottom: 2px solid #e2e8f0;">
                <th style="padding: 8px; text-align: left; color: #64748b; font-weight: 600;">
                    {{ $t('emails.donor_payment_method_expiring.campaign_label') }}
                </th>
                <th style="padding: 8px; text-align: left; color: #64748b; font-weight: 600;">
                    {{ $t('emails.donor_payment_method_expiring.organization_label') }}
                </th>
                <th style="padding: 8px; text-align: left; color: #64748b; font-weight: 600;">
                    {{ $t('emails.donor_payment_method_expiring.amount_label') }}
                </th>
                <th style="padding: 8px; text-align: left; color: #64748b; font-weight: 600;">
                    {{ $t('emails.donor_payment_method_expiring.frequency_label') }}
                </th>
            </tr>
        </thead>
        <tbody>
            @foreach ($subscriptions as $subscription)
                <tr style="border-bottom: 1px solid #e2e8f0;">
                    <td style="padding: 8px;">{{ $subscription->campaign->title }}</td>
                    <td style="padding: 8px;">{{ $subscription->campaign->organization->name }}</td>
                    <td style="padding: 8px; font-weight: 600;">{{ $subscription->displayAmount() }}</td>
                    <td style="padding: 8px;">{{ ucfirst($subscription->interval->value) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table style="width: 100%; border-collapse: collapse; margin: 24px 0;">
        <tr>
            <td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b; width: 40%;">
                {{ $t('emails.donor_payment_method_expiring.card_label') }}
            </td>
            <td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">
                {{ ucfirst($paymentMethod?->brand ?? '') }} ****{{ $paymentMethod?->last4 ?? '' }}
            </td>
        </tr>
        <tr>
            <td style="padding: 8px; color: #64748b;">
                {{ $t('emails.donor_payment_method_expiring.expiry_label') }}
            </td>
            <td style="padding: 8px;">{{ $expiryDate }}</td>
        </tr>
    </table>

    @if ($loginUrl)
        <p style="text-align: center; margin: 28px 0;">
            <a href="{{ $loginUrl }}" style="display: inline-block; background-color: #228B22; color: #ffffff; text-decoration: none; padding: 14px 28px; border-radius: 6px; font-size: 18px; font-weight: 600;">
                {{ $t('emails.donor_payment_method_expiring.cta') }}
            </a>
        </p>
    @endif

    <p style="font-size: 18px;">– {{ $t('emails.donor_payment_method_expiring.sign_off', ['organization' => $subscriptions->first()->campaign->organization->name]) }}</p>

    <p style="font-size: 16px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 16px; margin-top: 28px;">
        {{ $t('emails.donor_payment_method_expiring.footer_note') }}
    </p>

    <p style="font-size: 16px; color: #94a3b8;">
        {{ $t('emails.donor_payment_method_expiring.reason') }}
    </p>
@endsection
