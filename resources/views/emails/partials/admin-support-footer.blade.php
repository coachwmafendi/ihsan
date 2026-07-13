<div style="border-top: 1px solid #e2e8f0; padding-top: 16px; margin-top: 24px; font-size: 0.875rem; color: #64748b; line-height: 1.6;">
    <p class="email-small" style="margin: 0 0 8px;">
        If you have a question, contact us at
        <a href="mailto:{{ support_email() }}" style="color: #0d9488; text-decoration: underline;">{{ support_email() }}</a>
        and include email reference "<strong>{{ $emailReference }}</strong>" in your message.
        To manage your email settings
        <a href="{{ route('app.settings.notifications') }}" style="color: #0d9488; text-decoration: underline;">update your notification settings</a>.
    </p>

    <p class="email-small" style="margin: 0;">Sent with ❤️ from {{ config('app.name') }}</p>
</div>
