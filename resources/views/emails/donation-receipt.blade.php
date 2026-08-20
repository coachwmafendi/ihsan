@extends('emails.layouts.donor', ['organization' => $donation->campaign->organization, 'locale' => $locale])

@php
    $t = fn (string $key, array $replace = []) => trans($key, $replace, $locale);
@endphp

@section('preheader', $t('emails.receipt.preheader', ['amount' => $donation->total_charged_with_conversion, 'campaign' => $donation->campaign->title]))

@section('title', $t('emails.receipt.title'))

@section('content')
    <h1 style="color: #0f766e;">{{ $t('emails.receipt.title') }}</h1>

    <p>{{ $t('emails.common.greeting', ['name' => $donor->name]) }},</p>

    @php
        $hasCoveredFee = $donation->donor_fee_covered > 0;
        $totalCharged = $donation->total_charged_with_conversion;
    @endphp

    <p>{{ $t('emails.receipt.intro', ['amount' => $donation->total_charged_with_conversion, 'campaign' => $donation->campaign->title]) }}</p>

    @if ($donation->type === \App\Enums\DonationType::Recurring)
        <p><em>{{ $t('emails.receipt.recurring_note') }}</em></p>
    @endif

    <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
        @if ($hasCoveredFee)
            <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">{{ $t('emails.receipt.donation') }}</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; font-weight: 600;">{{ $donation->display_donation_amount }}</td></tr>
            <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">{{ $t('emails.receipt.processing_fee') }}</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">{{ $donation->display_fee_covered }}</td></tr>
            <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b; font-weight: 600;">{{ $t('emails.receipt.total_charged') }}</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; font-weight: 700;">{{ $totalCharged }}</td></tr>
        @else
            <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">{{ $t('emails.receipt.amount') }}</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; font-weight: 600;">{{ $donation->total_charged_with_conversion }}</td></tr>
        @endif
        <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">{{ $t('emails.receipt.campaign') }}</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{ $donation->campaign->title }}</td></tr>
        <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">{{ $t('emails.receipt.organization') }}</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{ $donation->campaign->organization->name }}</td></tr>
        <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">{{ $t('emails.receipt.date') }}</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{ myrTime($donation->created_at) }}</td></tr>
        <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">{{ $t('emails.receipt.payment_method') }}</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{ $donation->payment_method_display }}</td></tr>
        <tr><td style="padding: 8px; color: #64748b;">{{ $t('emails.receipt.status') }}</td><td style="padding: 8px; color: #16a34a; font-weight: 600;">{{ $t('emails.receipt.successful') }}</td></tr>
    </table>

    <p>{{ $t('emails.receipt.closing') }}</p>

    @if ($downloadUrl)
        <p style="margin: 24px 0;">
            <a href="{{ $downloadUrl }}" style="display: inline-block; background-color: #228B22; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 6px; font-weight: 600;">{{ $t('emails.receipt.download_receipt') }}</a>
        </p>
    @endif

    <p style="font-size: 0.875rem; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 16px; margin-top: 24px;">
        <a href="{{ route('donorportal.dashboard', $donation->campaign->organization) }}" style="color: #0d9488; text-decoration: underline;">{{ $t('emails.receipt.donor_portal_cta') }}</a>
        {{ $t('emails.receipt.donor_portal_text') }}
    </p>
@endsection
