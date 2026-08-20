@extends('emails.layouts.donor', ['organization' => $subscription->campaign->organization, 'locale' => $locale])

@php
    $t = fn (string $key, array $replace = []) => trans($key, $replace, $locale);

    $cardLabel = fn ($pm): string => $pm
        ? (ucfirst($pm->brand).' ****'.$pm->last4)
        : '';
@endphp

@section('preheader', $t('emails.donor_payment_method_changed.preheader', [
    'campaign' => $subscription->campaign->title,
]))

@section('title', $t('emails.donor_payment_method_changed.title'))

@section('content')
    <h1 style="font-size: 28px; color: #0f766e; margin: 0 0 16px;">
        {{ $t('emails.donor_payment_method_changed.title') }}
    </h1>

    <p style="font-size: 18px;">{{ $t('emails.common.greeting', ['name' => $donor->name]) }},</p>

    <p style="font-size: 18px;">
        {{ $t('emails.donor_payment_method_changed.intro', [
            'campaign' => $subscription->campaign->title,
        ]) }}
    </p>

    <table style="width: 100%; border-collapse: collapse; margin: 24px 0;">
        @if ($previousPaymentMethod)
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b; width: 40%;">
                    {{ $t('emails.donor_payment_method_changed.previous_card_label') }}
                </td>
                <td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{ $cardLabel($previousPaymentMethod) }}</td>
            </tr>
        @endif
        <tr>
            <td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">
                {{ $t('emails.donor_payment_method_changed.new_card_label') }}
            </td>
            <td style="padding: 8px; border-bottom: 1px solid #e2e8f0; font-weight: 600;">{{ $cardLabel($newPaymentMethod) }}</td>
        </tr>
        <tr>
            <td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">
                {{ $t('emails.donor_payment_method_changed.campaign_label') }}
            </td>
            <td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{ $subscription->campaign->title }}</td>
        </tr>
        <tr>
            <td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">
                {{ $t('emails.donor_payment_method_changed.amount_label') }}
            </td>
            <td style="padding: 8px; border-bottom: 1px solid #e2e8f0; font-weight: 600;">{{ $subscription->displayAmount() }}</td>
        </tr>
        <tr>
            <td style="padding: 8px; color: #64748b;">
                {{ $t('emails.donor_payment_method_changed.frequency_label') }}
            </td>
            <td style="padding: 8px;">{{ ucfirst($subscription->interval->value) }}</td>
        </tr>
    </table>

    @if ($loginUrl)
        <p style="text-align: center; margin: 28px 0;">
            <a href="{{ $loginUrl }}" style="display: inline-block; background-color: #228B22; color: #ffffff; text-decoration: none; padding: 14px 28px; border-radius: 6px; font-size: 18px; font-weight: 600;">
                {{ $t('emails.donor_payment_method_changed.cta') }}
            </a>
        </p>
    @endif

    <p style="font-size: 18px;">– {{ $t('emails.donor_payment_method_changed.sign_off', ['organization' => $subscription->campaign->organization->name]) }}</p>

    <p style="font-size: 16px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 16px; margin-top: 28px;">
        {{ $t('emails.donor_payment_method_changed.reason') }}
    </p>
@endsection
