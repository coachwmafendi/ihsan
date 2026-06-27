@extends('emails.layouts.donor', ['organization' => $donation->campaign->organization, 'locale' => $locale])

@php
    $t = fn (string $key, array $replace = []) => trans($key, $replace, $locale);
@endphp

@section('preheader', $t('emails.donor_refund.preheader', [
    'amount' => $amountDisplay,
    'campaign' => $donation->campaign->title,
    'organization' => $donation->campaign->organization->name,
]))

@section('title', $t('emails.donor_refund.title'))

@section('content')
    <h1 style="font-size: 24px; color: #dc2626;">{{ $t('emails.donor_refund.title') }}</h1>

    <p style="font-size: 18px;">{{ $t('emails.common.greeting', ['name' => $donor->name]) }},</p>

    <p style="font-size: 18px;">
        {{ $t('emails.donor_refund.intro', [
            'amount' => $amountDisplay,
            'campaign' => $donation->campaign->title,
            'organization' => $donation->campaign->organization->name,
        ]) }}
    </p>

    <table style="width: 100%; border-collapse: collapse; margin: 24px 0;">
        <tr>
            <td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b; width: 40%;">{{ $t('emails.donor_refund.amount_label') }}</td>
            <td style="padding: 8px; border-bottom: 1px solid #e2e8f0; font-weight: 600; color: #dc2626;">{{ $amountDisplay }}</td>
        </tr>
        <tr>
            <td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">{{ $t('emails.donor_refund.campaign_label') }}</td>
            <td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{ $donation->campaign->title }}</td>
        </tr>
        <tr>
            <td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">{{ $t('emails.donor_refund.donation_id_label') }}</td>
            <td style="padding: 8px; border-bottom: 1px solid #e2e8f0; font-family: monospace; font-size: 13px;">{{ $donation->public_id }}</td>
        </tr>
        <tr>
            <td style="padding: 8px; color: #64748b;">{{ $t('emails.donor_refund.date_label') }}</td>
            <td style="padding: 8px;">{{ myrTime($donation->refunded_at ?? $donation->updated_at) }}</td>
        </tr>
    </table>

    <p style="font-size: 18px;">{{ $t('emails.donor_refund.body') }}</p>

    <p style="font-size: 18px;">– {{ $t('emails.donor_refund.sign_off', ['organization' => $donation->campaign->organization->name]) }}</p>

    <p style="font-size: 16px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 16px; margin-top: 28px;">
        {{ $t('emails.donor_refund.reason') }}
    </p>

    <p style="font-size: 16px; color: #94a3b8;">
        <a href="{{ route('donorportal.dashboard', $donation->campaign->organization) }}" style="color: #0d9488; text-decoration: underline;">{{ $t('emails.receipt.donor_portal_cta') }}</a>
        {{ $t('emails.receipt.donor_portal_text') }}
    </p>
@endsection
