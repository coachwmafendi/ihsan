@extends('emails.layouts.donor', ['organization' => $organization, 'locale' => $locale])

@php
    $t = fn (string $key, array $replace = []) => trans($key, $replace, $locale);
@endphp

@section('preheader', $t('emails.magic_link.preheader', ['name' => $organization->name]))

@section('title', $t('emails.magic_link.title', ['name' => $organization->name]))

@section('content')
    <h2 style="color: #0f766e; margin: 0 0 16px 0;">{{ $t('emails.magic_link.title', ['name' => $organization->name]) }}</h2>

    <p>{{ $t('emails.common.greeting', ['name' => $donor->name]) }},</p>

    <p>{{ $t('emails.magic_link.intro') }}</p>

    <a href="{{ route('donorportal.magic-login', ['organization' => $organization, 'token' => $token]) }}"
       style="display: inline-block; padding: 12px 24px; background-color: #0f766e; color: white; text-decoration: none; border-radius: 6px; font-weight: 600; margin: 20px 0;">
        {{ $t('emails.magic_link.button') }}
    </a>

    <p style="color: #94a3b8; font-size: 14px;">{{ $t('emails.magic_link.expiry') }}</p>
@endsection
