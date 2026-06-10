<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

class MailtrapThrottle
{
    /**
     * Number of seconds to sleep between consecutive SMTP sends when using Mailtrap.
     */
    private const MAILTRAP_DELAY = 2;

    /**
     * Returns the configured stagger delay between emails (in seconds).
     */
    public static function delaySeconds(): int
    {
        return (int) config('mail.throttle_seconds', self::MAILTRAP_DELAY);
    }

    /**
     * Returns true if the current mail configuration is pointing to Mailtrap.
     */
    public static function isActive(): bool
    {
        $host = config('mail.mailers.smtp.host', '');

        return str_contains(strtolower($host), 'mailtrap');
    }

    /**
     * Pause execution briefly if we are on Mailtrap and emails are firing too rapidly.
     */
    public static function throttle(): void
    {
        if (! self::isActive() && self::delaySeconds() <= 0) {
            return;
        }

        $lastSent = Cache::get('mailtrap_last_sent');

        if ($lastSent !== null) {
            $elapsed = now()->timestamp - (int) $lastSent;
            $delay = self::delaySeconds() ?: self::MAILTRAP_DELAY;

            if ($elapsed < $delay) {
                sleep($delay - $elapsed);
            }
        }

        Cache::put('mailtrap_last_sent', now()->timestamp, 60);
    }
}
