@extends('emails.layouts.donor', ['organization' => $organization, 'locale' => $locale])

@php
    $t = fn (string $key, array $replace = []) => trans($key, $replace, $locale);
@endphp

@section('preheader', $t('emails.donor_problem_report.preheader'))

@section('title', $t('emails.donor_problem_report.title'))

@section('content')
    <h1 style="font-size: 24px; color: #0f766e;">{{ $t('emails.donor_problem_report.title') }}</h1>

    <p style="font-size: 18px;">{{ $t('emails.common.greeting', ['name' => $donor->name]) }},</p>

    <p style="font-size: 18px;">{{ $t('emails.donor_problem_report.intro') }}</p>

    {{-- The donor's own words back to them, so they have a record of what they sent. --}}
    <p style="margin: 24px 0 8px; font-size: 14px; font-weight: 600; color: #64748b;">
        {{ $t('emails.donor_problem_report.message_label') }}
    </p>
    <div style="border-left: 3px solid #cbd5e1; padding: 4px 0 4px 16px; color: #334155; font-size: 16px; white-space: pre-line;">{{ $reportMessage }}</div>

    <p style="font-size: 18px; margin-top: 24px;">{{ $t('emails.donor_problem_report.body') }}</p>

    <p style="font-size: 18px;">{{ $t('emails.donor_problem_report.sign_off', ['organization' => $organization->name]) }}</p>

    <p style="font-size: 0.875rem; color: #94a3b8;">{{ $t('emails.donor_problem_report.reason') }}</p>
@endsection
