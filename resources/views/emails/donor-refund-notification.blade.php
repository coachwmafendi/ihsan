@extends('emails.layouts.donor', ['organization' => $donation->campaign->organization, 'locale' => $locale])

@php
    $t = fn (string $key, array $replace = []) => trans($key, $replace, $locale);
    $organization = $donation->campaign->organization;
@endphp

@section('preheader', $t('emails.donor_refund.preheader', [
    'amount' => $amountDisplay,
    'campaign' => $donation->campaign->title,
    'organization' => $organization->name,
]))

@section('title', $t('emails.donor_refund.title'))

@section('content')
    <div style="text-align: center; margin-bottom: 24px;">
        <span style="display: inline-block; background-color: #fef2f2; color: #dc2626; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; padding: 6px 12px; border-radius: 9999px;">
            {{ $t('emails.donor_refund.status_label') }}
        </span>
    </div>

    <h1 style="font-size: 26px; color: #1a1a2e; text-align: center; margin: 0 0 8px 0;">
        {{ $t('emails.donor_refund.title') }}
    </h1>

    <p style="font-size: 18px; color: #64748b; text-align: center; margin: 0 0 24px 0;">
        {{ $t('emails.donor_refund.subtitle', ['organization' => $organization->name]) }}
    </p>

    <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; text-align: center; margin-bottom: 28px;">
        <p style="margin: 0 0 4px 0; font-size: 14px; color: #64748b;">{{ $t('emails.donor_refund.amount_label') }}</p>
        <p style="margin: 0; font-size: 32px; font-weight: 700; color: #dc2626;">{{ $amountDisplay }}</p>
    </div>

    <p style="font-size: 18px;">{{ $t('emails.common.greeting', ['name' => $donor->name]) }},</p>

    <p style="font-size: 18px;">
        {{ $t('emails.donor_refund.intro', [
            'amount' => $amountDisplay,
            'campaign' => $donation->campaign->title,
            'organization' => $organization->name,
        ]) }}
    </p>

    <table style="width: 100%; border-collapse: collapse; margin: 24px 0;">
        <tr>
            <td style="padding: 10px 8px; border-bottom: 1px solid #e2e8f0; color: #64748b; width: 40%;">{{ $t('emails.donor_refund.campaign_label') }}</td>
            <td style="padding: 10px 8px; border-bottom: 1px solid #e2e8f0; font-weight: 500;">
                <a href="{{ route('campaigns.public', $donation->campaign) }}" style="color: #0d9488; text-decoration: underline;">
                    {{ $donation->campaign->title }}
                </a>
            </td>
        </tr>
        <tr>
            <td style="padding: 10px 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">{{ $t('emails.donor_refund.donation_id_label') }}</td>
            <td style="padding: 10px 8px; border-bottom: 1px solid #e2e8f0; font-family: monospace; font-size: 13px;">{{ $donation->public_id }}</td>
        </tr>
        <tr>
            <td style="padding: 10px 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">{{ $t('emails.donor_refund.date_label') }}</td>
            <td style="padding: 10px 8px; border-bottom: 1px solid #e2e8f0;">{{ myrTime($donation->refunded_at ?? $donation->updated_at) }}</td>
        </tr>
        <tr>
            <td style="padding: 10px 8px; color: #64748b;">{{ $t('emails.donor_refund.payment_method_label') }}</td>
            <td style="padding: 10px 8px; font-weight: 500;">{{ $donation->payment_method_display }}</td>
        </tr>
    </table>

    <div style="background-color: #f0fdfa; border-left: 4px solid #14b8a6; border-radius: 6px; padding: 16px 20px; margin: 24px 0;">
        <p style="margin: 0 0 8px 0; font-weight: 700; color: #0f766e;">
            {{ $t('emails.donor_refund.timeline_heading') }}
        </p>
        <p style="margin: 0; color: #115e59;">
            {{ $t('emails.donor_refund.timeline_body') }}
        </p>
    </div>

    <p style="font-size: 16px; color: #475569;">
        {{ $t('emails.donor_refund.no_action_needed') }}
    </p>

    <p style="text-align: center; margin: 28px 0;">
        <a href="{{ route('donorportal.donations.detail', ['organization' => $organization, 'donation' => $donation]) }}" style="display: inline-block; background-color: #228B22; color: #ffffff; text-decoration: none; padding: 14px 28px; border-radius: 6px; font-size: 18px; font-weight: 600; margin: 0 6px 8px 0;">
            {{ $t('emails.donor_refund.cta_view') }}
        </a>

        @if (filled($organization->contact_email))
            <a href="mailto:{{ $organization->contact_email }}" style="display: inline-block; background-color: #ffffff; color: #228B22; border: 1px solid #228B22; text-decoration: none; padding: 14px 28px; border-radius: 6px; font-size: 18px; font-weight: 600; margin: 0 0 8px 6px;">
                {{ $t('emails.donor_refund.cta_contact') }}
            </a>
        @endif
    </p>

    <p style="font-size: 16px; color: #64748b; border-top: 1px solid #e2e8f0; padding-top: 16px; margin-top: 28px;">
        {{ $t('emails.donor_refund.support_intro', ['organization' => $organization->name]) }}
    </p>

    <p style="font-size: 16px; color: #64748b;">
        {{ $t('emails.donor_refund.body') }}
    </p>

    <p style="font-size: 18px; margin-top: 28px;">
        – {{ $t('emails.donor_refund.sign_off', ['organization' => $organization->name]) }}
    </p>

    <p style="font-size: 14px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 16px; margin-top: 28px;">
        {{ $t('emails.donor_refund.reason') }}
    </p>

    <p style="font-size: 14px; color: #94a3b8;">
        <a href="{{ route('donorportal.dashboard', $organization) }}" style="color: #0d9488; text-decoration: underline;">
            {{ $t('emails.receipt.donor_portal_cta') }}
        </a>
        {{ $t('emails.receipt.donor_portal_text') }}
    </p>
@endsection
