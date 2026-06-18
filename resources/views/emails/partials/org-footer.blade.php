@php
    $address = collect([
        $organization->address_line_1,
        $organization->address_line_2,
        $organization->city,
        $organization->state,
        $organization->postcode,
        $organization->country,
    ])->filter()->implode(', ');
@endphp

<div style="margin-top: 32px; padding-top: 24px; border-top: 1px solid #e2e8f0; text-align: center; color: #64748b; font-size: 0.875rem; line-height: 1.6;">
    <p style="margin: 0 0 4px 0; font-weight: 600;">{{ $organization->name }}</p>

    @if (filled($address))
        <p style="margin: 0 0 4px 0;">{{ $address }}</p>
    @endif

    @if (filled($organization->contact_phone))
        <p style="margin: 0 0 4px 0;">
            Tel:
            <a href="tel:{{ $organization->contact_phone }}" style="color: #64748b; text-decoration: underline;">
                {{ $organization->contact_phone }}
            </a>
        </p>
    @endif

    @if (filled($organization->contact_email))
        <p style="margin: 0 0 4px 0;">
            Email:
            <a href="mailto:{{ $organization->contact_email }}" style="color: #64748b; text-decoration: underline;">
                {{ $organization->contact_email }}
            </a>
        </p>
    @endif

    @if (filled($organization->website_url))
        <p style="margin: 0 0 16px 0;">
            <a href="{{ $organization->website_url }}" target="_blank" rel="noopener noreferrer" style="color: #64748b; text-decoration: underline;">
                {{ $organization->website_url }}
            </a>
        </p>
    @endif

    @if (isset($unsubscribeUrl))
        <p style="margin: 0; font-size: 0.8125rem;">
            <a href="{{ $unsubscribeUrl }}" target="_blank" rel="noopener noreferrer" style="color: #64748b; text-decoration: underline;">
                Don’t send me these emails anymore
            </a>
        </p>
    @endif
</div>
