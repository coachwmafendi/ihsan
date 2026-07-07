<?php

namespace App\Support;

use App\Models\Element;
use Illuminate\Support\Str;

class EmbedWidget
{
    public static function scriptUrl(): string
    {
        return rtrim(config('app.url'), '/').'/e/widget.js?v='.self::version();
    }

    public static function version(): string
    {
        return once(function (): string {
            $path = resource_path('js/widget.js');

            if (is_file($path)) {
                $hash = sha1_file($path);

                if (is_string($hash) && $hash !== '') {
                    return substr($hash, 0, 12);
                }

                $modifiedAt = filemtime($path);

                if ($modifiedAt !== false) {
                    return (string) $modifiedAt;
                }
            }

            return (string) config('app.version', '1');
        });
    }

    public static function staticQrCodeHtml(Element $element): string
    {
        $config = $element->config ?? [];
        $label = e($config['label'] ?? $config['button_text'] ?? $config['text'] ?? 'Scan to donate');
        $rawAlignment = $config['alignment'] ?? 'center';
        $alignment = in_array($rawAlignment, ['left', 'center', 'right'], true)
            ? $rawAlignment
            : 'center';

        $rawSize = $config['size'] ?? 'medium';
        if (is_numeric($rawSize)) {
            $size = (int) $rawSize;
        } else {
            $size = match (strtolower($rawSize)) {
                'small' => 150,
                'medium' => 200,
                'large' => 250,
                'extra large', 'extra_large' => 300,
                default => 200,
            };
        }

        $campaignUrl = $element->campaign !== null
            ? url('/donate/'.$element->token)
            : url('/');

        $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size='.$size.'x'.$size.'&data='.urlencode($campaignUrl).'&bgcolor=ffffff&color=0f172a&qzone=2';

        return sprintf(
            '<div class="ihsan-embed-placeholder ihsan-qr-code-placeholder" data-ihsan-token="%s" style="text-align:%s;"><img src="%s" alt="%s" width="%d" height="%d" style="display:block;margin:0 auto;border-radius:12px;border:1px solid #e2e8f0;"><p style="margin:8px 0 0;font-size:14px;font-weight:500;color:#475569;text-align:center;">%s</p></div>',
            e($element->token),
            $alignment,
            e($qrUrl),
            $label,
            $size,
            $size,
            $label
        );
    }

    public static function staticFloatingButtonPlaceholderHtml(Element $element): string
    {
        $config = $element->config ?? [];
        $text = e($config['button_text'] ?? $config['text'] ?? 'Donate');
        $position = $config['position'] ?? 'bottom-right';

        $labelStyle = 'border:2px dashed #cbd5e1;border-radius:8px;padding:16px;text-align:center;color:#64748b;font-weight:600;font-family:sans-serif;font-size:14px;line-height:1.4;';

        $note = match ($position) {
            'bottom-right' => 'Bottom right corner',
            'bottom-left' => 'Bottom left corner',
            'top-right' => 'Top right corner',
            'top-left' => 'Top left corner',
            'vertical_left_center', 'vertical-left-center' => 'Middle left edge',
            'vertical_right_center', 'vertical-right-center' => 'Middle right edge',
            default => 'Floating on scroll',
        };

        return sprintf(
            '<div class="ihsan-embed-placeholder ihsan-floating-button-placeholder" data-ihsan-token="%s" style="%s">Ihsan Floating Button — %s<div style="margin-top:6px;font-size:12px;font-weight:400;color:#94a3b8;">%s</div></div>',
            e($element->token),
            $labelStyle,
            $text,
            $note
        );
    }

    public static function staticLinkHtml(Element $element): string
    {
        $config = array_merge([
            'text' => 'Donate',
            'button_text' => 'Donate',
            'style' => 'button',
            'color' => 'campaign',
            'button_color' => 'bg-blue-600 hover:bg-blue-700',
            'button_size' => 'medium',
            'corner_radius' => 8,
            'button_icon' => 'none',
            'alignment' => 'center',
            'action' => 'checkout_modal',
        ], $element->config ?? []);

        $campaignFormParameter = $element->campaign !== null && $element->campaign->checkout_modal_enabled
            ? $element->campaign->form_parameter
            : null;

        $action = $config['action'];
        $openInPopup = in_array($action, ['checkout_modal', 'open_checkout_modal'], true);

        if ($campaignFormParameter) {
            $url = url('/checkout/'.$campaignFormParameter.($openInPopup ? '?popup=1' : ''));
        } elseif ($element->campaign !== null) {
            $url = url('/donate/'.$element->token.($openInPopup ? '?popup=1' : ''));
        } else {
            $url = url('/');
        }

        $text = e($config['text'] ?: $config['button_text']);
        $alignment = in_array($config['alignment'], ['left', 'center', 'right'], true)
            ? $config['alignment']
            : 'center';
        $colour = self::resolveColour(($config['button_color'] ?? '') ?: ($config['color'] ?? 'campaign'));
        [$padding, $fontSize] = self::resolveSize($config['button_size']);
        $radius = ((int) $config['corner_radius']).'px';
        $icon = ($config['button_icon'] ?? 'none') !== 'none'
            ? self::iconSvg($config['button_icon'], 16)
            : '';

        $anchorStyle = ($config['style'] ?? 'button') === 'button'
            ? sprintf(
                'display:inline-flex;align-items:center;justify-content:center;gap:6px;cursor:pointer !important;text-decoration:none;font-weight:600;line-height:1.3;white-space:nowrap;letter-spacing:.01em;color:#fff;background:%s;padding:%s;font-size:%s;border-radius:%s;box-shadow:0 2px 8px rgba(0,0,0,.12);font-family:system-ui,-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif;',
                $colour,
                $padding,
                $fontSize,
                $radius
            )
            : sprintf(
                'color:%s;font-size:%s;font-weight:500;text-decoration:underline;text-underline-offset:2px;cursor:pointer !important;font-family:system-ui,-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif;',
                $colour,
                $fontSize
            );

        $anchor = sprintf(
            '<a href="%s" class="ihsan-link" data-ihsan-token="%s" target="_blank" rel="noopener" style="%s">%s<span>%s</span></a>',
            e($url),
            e($element->token),
            $anchorStyle,
            $icon,
            $text
        );

        return sprintf('<div style="text-align:%s;">%s</div>', $alignment, $anchor);
    }

    public static function staticButtonHtml(Element $element): string
    {
        $config = array_merge([
            'button_text' => 'Donate',
            'button_color' => 'bg-blue-600 hover:bg-blue-700',
            'button_size' => 'text-base px-6 py-3',
            'corner_radius' => 8,
            'button_icon' => 'heart',
            'alignment' => 'center',
            'action' => 'checkout_modal',
        ], $element->config ?? []);

        $campaignFormParameter = $element->campaign !== null && $element->campaign->checkout_modal_enabled
            ? $element->campaign->form_parameter
            : null;

        $action = $config['action'];
        $openInPopup = in_array($action, ['checkout_modal', 'open_checkout_modal'], true);

        if ($campaignFormParameter) {
            $url = url('/checkout/'.$campaignFormParameter.($openInPopup ? '?popup=1' : ''));
        } elseif ($element->campaign !== null) {
            $url = url('/donate/'.$element->token.($openInPopup ? '?popup=1' : ''));
        } else {
            $url = url('/');
        }

        $colour = self::resolveColour($config['button_color']);
        [$padding, $fontSize] = self::resolveSize($config['button_size']);
        $radius = ((int) $config['corner_radius']).'px';
        $iconSvg = self::iconSvg($config['button_icon'] ?? 'heart', 18);
        $text = e($config['button_text']);

        $alignment = in_array($config['alignment'], ['left', 'center', 'right'], true)
            ? $config['alignment']
            : 'center';

        $buttonStyle = sprintf(
            'display:inline-flex;align-items:center;justify-content:center;gap:7px;cursor:pointer !important;text-decoration:none;font-weight:600;line-height:1.3;white-space:nowrap;letter-spacing:.01em;color:#fff;background:%s;padding:%s;font-size:%s;border-radius:%s;box-shadow:0 3px 12px rgba(0,0,0,.15);font-family:system-ui,-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif;',
            $colour,
            $padding,
            $fontSize,
            $radius
        );

        $anchor = sprintf(
            '<a href="%s" class="ihsan-button" data-ihsan-token="%s" target="_blank" rel="noopener" style="%s">%s<span>%s</span></a>',
            e($url),
            e($element->token),
            $buttonStyle,
            $iconSvg,
            $text
        );

        return sprintf('<div style="text-align:%s;">%s</div>', $alignment, $anchor);
    }

    private static function resolveColour(string $raw): string
    {
        $raw = Str::lower($raw);

        $colours = [
            'campaign' => '#16a34a',
            'blue' => '#2563eb',
            'teal' => '#0d9488',
            'green' => '#16a34a',
            'orange' => '#ea580c',
            'red' => '#dc2626',
            'purple' => '#9333ea',
            'dark' => '#1e293b',
            'slate' => '#1e293b',
            'gray' => '#1e293b',
        ];

        foreach ($colours as $name => $hex) {
            if (str_contains($raw, $name)) {
                return $hex;
            }
        }

        if (str_starts_with($raw, '#')) {
            return $raw;
        }

        return $colours['campaign'];
    }

    /** @return array{0: string, 1: string} */
    private static function resolveSize(string $raw): array
    {
        $raw = Str::lower($raw);
        $padding = '12px 24px';
        $fontSize = '16px';

        if (str_contains($raw, 'px-4') || str_contains($raw, 'small')) {
            $padding = '8px 16px';
            $fontSize = '14px';
        } elseif (str_contains($raw, 'px-8') || str_contains($raw, 'large')) {
            $padding = '16px 32px';
            $fontSize = '18px';
        } elseif (str_contains($raw, 'px-6') || str_contains($raw, 'medium')) {
            $padding = '12px 24px';
            $fontSize = '16px';
        }

        foreach (['text-sm:14px', 'text-base:16px', 'text-lg:18px', 'text-xl:20px'] as $tokenSize) {
            [$cls, $size] = explode(':', $tokenSize);
            if (str_contains($raw, $cls)) {
                $fontSize = $size;
                break;
            }
        }

        return [$padding, $fontSize];
    }

    private static function iconSvg(string $icon, int $size): string
    {
        if ($icon === 'none') {
            return '';
        }

        $icons = [
            'heart' => '<svg viewBox="0 0 24 24" fill="currentColor" width="SIZE" height="SIZE"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>',
            'hand' => '<svg viewBox="0 0 24 24" fill="currentColor" width="SIZE" height="SIZE"><path d="M23 12.22V15c0 4.97-4.03 9-9 9H9.17c-1.59 0-3.11-.63-4.24-1.76L0 17.5l1.5-1.5c.47-.47 1.08-.73 1.77-.73.46 0 .9.12 1.28.35L7 17.34V4.5C7 3.67 7.67 3 8.5 3S10 3.67 10 4.5v4h1V3.5C11 2.67 11.67 2 12.5 2S14 2.67 14 3.5V8.5h1V4c0-.83.67-1.5 1.5-1.5S18 3.17 18 4v5.5h1V6c0-.83.67-1.5 1.5-1.5S22 5.17 22 6v6.22z"/></svg>',
            'star' => '<svg viewBox="0 0 24 24" fill="currentColor" width="SIZE" height="SIZE"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>',
            'gift' => '<svg viewBox="0 0 24 24" fill="currentColor" width="SIZE" height="SIZE"><path d="M20 6h-2.18c.11-.31.18-.65.18-1 0-1.66-1.34-3-3-3-1.05 0-1.96.54-2.5 1.35l-.5.67-.5-.68C10.96 2.54 10.05 2 9 2 7.34 2 6 3.34 6 5c0 .35.07.69.18 1H4c-1.11 0-1.99.89-1.99 2L2 19c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V8c0-1.11-.89-2-2-2zm-5-2c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zM9 4c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm11 15H4v-2h16v2zm0-5H4V8h5.08L7 10.83 8.62 12 12 7.01l3.38 4.99L17 10.83 14.92 8H20v6z"/></svg>',
            'plus' => '<svg viewBox="0 0 24 24" fill="currentColor" width="SIZE" height="SIZE"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>',
        ];

        return str_replace('SIZE', (string) $size, $icons[$icon] ?? $icons['heart']);
    }
}
