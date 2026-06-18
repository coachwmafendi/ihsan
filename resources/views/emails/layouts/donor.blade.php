@php($locale = $locale ?? config('app.locale'))
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $locale) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@hasSection('title')@yield('title') — @endif{{ config('app.name') }}</title>
</head>
<body style="margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; line-height: 1.6; color: #1a1a2e; background-color: #f8fafc;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
        <tr>
            <td align="center" style="padding: 20px 10px;">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="max-width: 600px; width: 100%; background-color: #ffffff; border-radius: 8px; overflow: hidden;">
                    <tr>
                        <td style="padding: 20px; border-bottom: 1px solid #e2e8f0;">
                            @if (filled($organization->logo_path))
                                <img src="{{ route('organization.logo', $organization) }}" alt="{{ $organization->name }}" style="max-height: 40px; width: auto; display: block;">
                            @else
                                <p style="margin: 0; font-size: 18px; font-weight: 700; color: #0f766e;">{{ $organization->name }}</p>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 24px 20px;">
                            @yield('content')
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 0 20px 24px;">
                            @include('emails.partials.org-footer', ['organization' => $organization])
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
