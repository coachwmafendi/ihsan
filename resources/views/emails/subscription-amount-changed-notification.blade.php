@extends('emails.layouts.donor', ['organization' => $organization, 'locale' => $locale])

@php
    $t = fn (string $key, array $replace = []) => trans($key, $replace, $locale);
    $intervalLabel = $t('emails.subscription_amount_changed.interval_'.$subscription->interval->value);
    $frequencyLabel = $t('emails.subscription_amount_changed.frequency_'.$subscription->interval->value);

    $formatAmount = fn (float $amount): string => $subscription->currency_symbol.' '.number_format($amount, 2);
    $formatDate = fn (?Carbon\CarbonInterface $date): string => $date ? $date->format('M d, Y') : '-';

    $paymentMethodLabel = function (?App\Models\DonorPaymentMethod $method): string {
        if ($method === null) {
            return '-';
        }

        $parts = array_filter([
            $method->brand,
            filled($method->last4) ? '•••• '.$method->last4 : null,
            $method->exp_month && $method->exp_year
                ? str_pad((string) $method->exp_month, 2, '0', STR_PAD_LEFT).'/'.$method->exp_year
                : null,
        ]);

        return implode(', ', $parts) ?: '-';
    };

    $statusLabel = fn (App\Enums\SubscriptionStatus $status): string => $t('emails.subscription_amount_changed.status_'.$status->value);
@endphp

@section('preheader', $t('emails.subscription_amount_changed.preheader', [
    'campaign' => $subscription->campaign->title,
]))

@section('title', $t('emails.subscription_amount_changed.title'))

@section('content')
    @if (! $isDonor)
        @push('styles')
            <style type="text/css">
                @media only screen and (min-width: 600px) {
                    .email-content { font-size: 17px !important; line-height: 1.65 !important; }
                    .email-content h1 { font-size: 28px !important; }
                    .email-content p, .email-content td, .email-content th { font-size: 17px !important; }
                    .email-content .email-small { font-size: 15px !important; }
                }
            </style>
        @endpush
    @endif

    <h1 style="font-size: 26px; color: #0f172a; margin: 0 0 12px;">{{ $t('emails.subscription_amount_changed.title') }}</h1>

    <p style="color: #475569; margin: 0 0 24px;">
        {{ $t('emails.subscription_amount_changed.heading') }}
    </p>

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
                'previous' => $formatAmount($previousAmount ?? $subscription->amount),
                'amount' => $formatAmount($subscription->amount),
                'interval' => $intervalLabel,
            ]) }}
        @else
            {{ $t('emails.subscription_amount_changed.intro_admin', [
                'donor' => $donor->name,
                'campaign' => $subscription->campaign->title,
                'previous' => $formatAmount($previousAmount ?? $subscription->amount),
                'amount' => $formatAmount($subscription->amount),
                'interval' => $intervalLabel,
            ]) }}
        @endif
    </p>

    <table style="width: 100%; border-collapse: collapse; margin: 24px 0; font-size: 16px;">
        <tr>
            <td style="padding: 10px 8px; border-bottom: 1px solid #e2e8f0; color: #64748b; width: 45%; vertical-align: top;">
                {{ $t('emails.subscription_amount_changed.recurring_plan_id') }}
            </td>
            <td style="padding: 10px 8px; border-bottom: 1px solid #e2e8f0; font-weight: 600; vertical-align: top;">
                {{ $subscription->public_id }}
            </td>
        </tr>
        <tr>
            <td style="padding: 10px 8px; border-bottom: 1px solid #e2e8f0; color: #64748b; vertical-align: top;">
                {{ $t('emails.subscription_amount_changed.status') }}
            </td>
            <td style="padding: 10px 8px; border-bottom: 1px solid #e2e8f0; vertical-align: top;">
                @if ($statusChanged && $previousStatus !== null)
                    <div style="color: #94a3b8; text-decoration: line-through;">{{ $statusLabel($previousStatus) }}</div>
                @endif
                <div style="font-weight: 600;">{{ $statusLabel($subscription->status) }}</div>
            </td>
        </tr>
        <tr>
            <td style="padding: 10px 8px; border-bottom: 1px solid #e2e8f0; color: #64748b; vertical-align: top;">
                {{ $t('emails.subscription_amount_changed.amount') }}
            </td>
            <td style="padding: 10px 8px; border-bottom: 1px solid #e2e8f0; vertical-align: top;">
                @if ($amountChanged && $previousAmount !== null)
                    <div style="color: #94a3b8; text-decoration: line-through;">{{ $formatAmount($previousAmount) }}</div>
                @endif
                <div style="font-weight: 600;">{{ $formatAmount($subscription->amount) }}</div>
            </td>
        </tr>
        <tr>
            <td style="padding: 10px 8px; border-bottom: 1px solid #e2e8f0; color: #64748b; vertical-align: top;">
                {{ $t('emails.subscription_amount_changed.payment_method') }}
            </td>
            <td style="padding: 10px 8px; border-bottom: 1px solid #e2e8f0; vertical-align: top;">
                @if ($paymentMethodChanged && $previousPaymentMethod !== null)
                    <div style="color: #94a3b8; text-decoration: line-through;">{{ $paymentMethodLabel($previousPaymentMethod) }}</div>
                @endif
                <div style="font-weight: 600;">{{ $paymentMethodLabel($currentPaymentMethod) }}</div>
            </td>
        </tr>
        <tr>
            <td style="padding: 10px 8px; border-bottom: 1px solid #e2e8f0; color: #64748b; vertical-align: top;">
                {{ $t('emails.subscription_amount_changed.frequency') }}
            </td>
            <td style="padding: 10px 8px; border-bottom: 1px solid #e2e8f0; vertical-align: top;">
                {{ $frequencyLabel }}
            </td>
        </tr>
        <tr>
            <td style="padding: 10px 8px; border-bottom: 1px solid #e2e8f0; color: #64748b; vertical-align: top;">
                {{ $t('emails.subscription_amount_changed.next_installment') }}
            </td>
            <td style="padding: 10px 8px; border-bottom: 1px solid #e2e8f0; vertical-align: top;">
                {{ $formatDate($subscription->next_charge_at) }}
            </td>
        </tr>
        <tr>
            <td style="padding: 10px 8px; border-bottom: 1px solid #e2e8f0; color: #64748b; vertical-align: top;">
                {{ $t('emails.subscription_amount_changed.supporter') }}
            </td>
            <td style="padding: 10px 8px; border-bottom: 1px solid #e2e8f0; vertical-align: top;">
                {{ $subscription->donor->name }}
            </td>
        </tr>
        <tr>
            <td style="padding: 10px 8px; border-bottom: 1px solid #e2e8f0; color: #64748b; vertical-align: top;">
                {{ $t('emails.subscription_amount_changed.email') }}
            </td>
            <td style="padding: 10px 8px; border-bottom: 1px solid #e2e8f0; vertical-align: top;">
                <a href="mailto:{{ $subscription->donor->email }}" style="color: #2563eb; text-decoration: underline;">{{ $subscription->donor->email }}</a>
            </td>
        </tr>
        <tr>
            <td style="padding: 10px 8px; border-bottom: 1px solid #e2e8f0; color: #64748b; vertical-align: top;">
                {{ $t('emails.subscription_amount_changed.account') }}
            </td>
            <td style="padding: 10px 8px; border-bottom: 1px solid #e2e8f0; vertical-align: top;">
                {{ $organization?->name ?? config('app.name') }}
            </td>
        </tr>
        <tr>
            <td style="padding: 10px 8px; border-bottom: 1px solid #e2e8f0; color: #64748b; vertical-align: top;">
                {{ $t('emails.subscription_amount_changed.campaign') }}
            </td>
            <td style="padding: 10px 8px; border-bottom: 1px solid #e2e8f0; vertical-align: top;">
                {{ $subscription->campaign->title }}
            </td>
        </tr>
        <tr>
            <td style="padding: 10px 8px; border-bottom: 1px solid #e2e8f0; color: #64748b; vertical-align: top;">
                {{ $t('emails.subscription_amount_changed.first_installment') }}
            </td>
            <td style="padding: 10px 8px; border-bottom: 1px solid #e2e8f0; vertical-align: top;">
                {{ $formatDate($firstDonation?->created_at ?? $subscription->created_at) }}
            </td>
        </tr>
        <tr>
            <td style="padding: 10px 8px; border-bottom: 1px solid #e2e8f0; color: #64748b; vertical-align: top;">
                {{ $t('emails.subscription_amount_changed.last_installment') }}
            </td>
            <td style="padding: 10px 8px; border-bottom: 1px solid #e2e8f0; vertical-align: top;">
                {{ $formatDate($subscription->last_charge_at) }}
            </td>
        </tr>
        <tr>
            <td style="padding: 10px 8px; border-bottom: 1px solid #e2e8f0; color: #64748b; vertical-align: top;">
                {{ $t('emails.subscription_amount_changed.processed_installments') }}
            </td>
            <td style="padding: 10px 8px; border-bottom: 1px solid #e2e8f0; vertical-align: top;">
                {{ $subscription->payment_count }}
            </td>
        </tr>
        <tr>
            <td style="padding: 10px 8px; color: #64748b; vertical-align: top;">
                {{ $t('emails.subscription_amount_changed.total_amount') }}
            </td>
            <td style="padding: 10px 8px; font-weight: 600; vertical-align: top;">
                {{ $formatAmount($totalAmount) }}
            </td>
        </tr>
    </table>

    <p style="margin: 28px 0 8px; text-align: center;">
        <a href="{{ $viewUrl }}" style="display: inline-block; background-color: #2563eb; color: #ffffff; text-decoration: none; padding: 14px 28px; border-radius: 8px; font-weight: 600; font-size: 16px;">
            {{ $t('emails.subscription_amount_changed.view_in_app', ['app' => config('app.name')]) }}
        </a>
    </p>

    <p class="email-small" style="font-size: 0.875rem; color: #94a3b8; margin-top: 24px;">
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
