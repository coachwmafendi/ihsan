<p class="email-small" style="margin: 16px 0 4px; font-size: 12px; color: #94a3b8;">
    If you have a question, contact us at <a href="mailto:{{ support_email() }}" style="color: #0f766e; text-decoration: underline;">{{ support_email() }}</a> and include email reference "{{ $organization->public_id ?? '' }}" in your message. To manage your email settings <a href="{{ route('app.settings.notifications') }}" target="_blank" rel="noopener noreferrer" style="color: #0f766e; text-decoration: underline;">update your notification settings</a>.
</p>
<p class="email-small" style="margin: 0; font-size: 12px; color: #94a3b8;">Sent with ❤️ from {{ config('app.name') }}</p>
