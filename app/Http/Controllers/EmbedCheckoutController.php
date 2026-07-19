<?php

namespace App\Http\Controllers;

use App\Enums\CampaignStatus;
use App\Models\Campaign;
use App\Models\Element;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Js;
use Illuminate\Support\Str;

class EmbedCheckoutController extends Controller
{
    public function widget(): Response
    {
        $path = resource_path('js/widget.js');

        $script = file_exists($path) ? file_get_contents($path) : '';

        return response($script, 200, [
            'Content-Type' => 'application/javascript',
            'Cache-Control' => 'no-cache, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    public function loader(): Response
    {
        $path = resource_path('js/loader.js');

        $script = file_exists($path) ? file_get_contents($path) : '';

        return response($script, 200, [
            'Content-Type' => 'application/javascript; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    public function button(string $token): Response
    {
        $element = Element::query()
            ->where('token', $token)
            ->where('is_active', true)
            ->whereNull('archived_at')
            ->with('campaign')
            ->first();

        if (
            ! $element
            || ($element->campaign !== null && $element->campaign->status !== CampaignStatus::Active)
        ) {
            abort(404);
        }

        $settings = array_merge([
            'button_text' => 'Donate',
            'button_color' => 'bg-blue-600 hover:bg-blue-700',
            'button_size' => 'text-base px-6 py-3',
            'corner_radius' => 8,
            'button_icon' => 'heart',
            'button_effect' => 'none',
            'alignment' => 'center',
            'action' => 'checkout_modal',
        ], $element->config ?? []);

        $url = $element->campaign
            ? url('/donate/'.$element->token.'?popup=1')
            : url('/');

        $rawColor = Str::lower((string) $settings['button_color']);
        $colorHex = '#2563eb';
        $colors = [
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
        foreach ($colors as $name => $hex) {
            if (str_contains($rawColor, $name)) {
                $colorHex = $hex;
                break;
            }
        }

        $rawSize = Str::lower((string) $settings['button_size']);
        $padding = '12px 24px';
        $fontSize = '16px';

        if (str_contains($rawSize, 'px-4') || str_contains($rawSize, 'small')) {
            $padding = '8px 16px';
            $fontSize = '14px';
        } elseif (str_contains($rawSize, 'px-8') || str_contains($rawSize, 'large')) {
            $padding = '16px 32px';
            $fontSize = '18px';
        } elseif (str_contains($rawSize, 'px-6') || str_contains($rawSize, 'medium')) {
            $padding = '12px 24px';
            $fontSize = '16px';
        }

        foreach (['text-sm:14px', 'text-base:16px', 'text-lg:18px', 'text-xl:20px'] as $tokenSize) {
            [$cls, $size] = explode(':', $tokenSize);
            if (str_contains($rawSize, $cls)) {
                $fontSize = $size;
                break;
            }
        }

        $radius = ((int) $settings['corner_radius']).'px';

        $gradientEffects = [
            'gradient_teal_green' => 'linear-gradient(120deg,#0d9488,#14b8a6,#22c55e,#0d9488)',
            'gradient_blue_purple' => 'linear-gradient(120deg,#2563eb,#7c3aed,#a855f7,#2563eb)',
            'gradient_orange_red' => 'linear-gradient(120deg,#ea580c,#dc2626,#f97316,#ea580c)',
            'gradient_rose_pink' => 'linear-gradient(120deg,#f43f5e,#ec4899,#fb7185,#f43f5e)',
            'gradient_amber_orange' => 'linear-gradient(120deg,#f59e0b,#ea580c,#fbbf24,#f59e0b)',
            'gradient_cyan_blue' => 'linear-gradient(120deg,#06b6d4,#2563eb,#38bdf8,#06b6d4)',
            'gradient_emerald_teal' => 'linear-gradient(120deg,#10b981,#0d9488,#34d399,#10b981)',
            'gradient_indigo_purple' => 'linear-gradient(120deg,#6366f1,#7c3aed,#818cf8,#6366f1)',
            'gradient_gold_amber' => 'linear-gradient(120deg,#eab308,#f59e0b,#fcd34d,#eab308)',
            'gradient_pink_purple' => 'linear-gradient(120deg,#ec4899,#a855f7,#f472b6,#ec4899)',
        ];
        $effect = $gradientEffects[$settings['button_effect']] ?? null;
        $background = $effect ?: $colorHex;

        $icons = [
            'heart' => '<svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>',
            'hand' => '<svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M23 12.22V15c0 4.97-4.03 9-9 9H9.17c-1.59 0-3.11-.63-4.24-1.76L0 17.5l1.5-1.5c.47-.47 1.08-.73 1.77-.73.46 0 .9.12 1.28.35L7 17.34V4.5C7 3.67 7.67 3 8.5 3S10 3.67 10 4.5v4h1V3.5C11 2.67 11.67 2 12.5 2S14 2.67 14 3.5V8.5h1V4c0-.83.67-1.5 1.5-1.5S18 3.17 18 4v5.5h1V6c0-.83.67-1.5 1.5-1.5S22 5.17 22 6v6.22z"/></svg>',
            'star' => '<svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>',
            'gift' => '<svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M20 6h-2.18c.11-.31.18-.65.18-1 0-1.66-1.34-3-3-3-1.05 0-1.96.54-2.5 1.35l-.5.67-.5-.68C10.96 2.54 10.05 2 9 2 7.34 2 6 3.34 6 5c0 .35.07.69.18 1H4c-1.11 0-1.99.89-1.99 2L2 19c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V8c0-1.11-.89-2-2-2zm-5-2c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zM9 4c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm11 15H4v-2h16v2zm0-5H4V8h5.08L7 10.83 8.62 12 12 7.01l3.38 4.99L17 10.83 14.92 8H20v6z"/></svg>',
            'plus' => '<svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>',
        ];
        $iconSvg = $icons[$settings['button_icon']] ?? $icons['heart'];
        if ($settings['button_icon'] === 'none') {
            $iconSvg = '';
        }

        return response()->view('embed.button', [
            'text' => e($settings['button_text']),
            'url' => $url,
            'iconSvg' => $iconSvg,
            'background' => $background,
            'padding' => $padding,
            'fontSize' => $fontSize,
            'radius' => $radius,
            'alignment' => in_array($settings['alignment'], ['left', 'center', 'right'], true)
                ? $settings['alignment']
                : 'center',
            'openInPopup' => true,
        ]);
    }

    public function script(Request $request): Response
    {
        $checkoutBaseUrl = $request->getSchemeAndHttpHost().'/checkout';
        $donateBaseUrl = $request->getSchemeAndHttpHost().'/donate/campaign';

        $script = <<<'JS'
(function () {
    if (window.IhsanCheckout) {
        return;
    }

    var checkoutBaseUrl = CHECKOUT_BASE_URL;
    var donateBaseUrl = DONATE_BASE_URL;
    var modalId = 'ihsan-checkout-modal';
    var mobileBreakpoint = 768;

    function isMobile() {
        return window.innerWidth <= mobileBreakpoint;
    }

    function formFromUrl(url) {
        try {
            return new URL(url, window.location.href).searchParams.get('form');
        } catch (error) {
            return null;
        }
    }

    function ensureModal() {
        var existing = document.getElementById(modalId);

        if (existing) {
            return existing;
        }

        var modal = document.createElement('div');
        modal.id = modalId;
        modal.setAttribute('role', 'dialog');
        modal.setAttribute('aria-modal', 'true');
        modal.style.cssText = 'position:fixed;inset:0;z-index:2147483647;display:none;align-items:center;justify-content:center;background:rgba(15,23,42,.58);padding:20px;';
        modal.innerHTML = '<div style="position:relative;width:min(100%,520px);height:min(94vh,820px);background:#fff;border-radius:18px;box-shadow:0 24px 80px rgba(15,23,42,.28);overflow:hidden;"><button type="button" data-ihsan-close style="position:absolute;top:10px;right:10px;z-index:2;width:34px;height:34px;border:0;border-radius:999px;background:rgba(15,23,42,.08);font:24px/1 system-ui,sans-serif;cursor:pointer;">&times;</button><iframe title="Ihsan checkout" data-ihsan-frame style="width:100%;height:100%;border:0;"></iframe></div>';

        modal.addEventListener('click', function (event) {
            if (event.target === modal || event.target.closest('[data-ihsan-close]')) {
                window.IhsanCheckout.close();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                window.IhsanCheckout.close();
            }
        });

        document.body.appendChild(modal);

        return modal;
    }

    window.IhsanCheckout = {
        open: function (form) {
            if (!form) {
                return;
            }

            if (isMobile()) {
                window.location.href = donateBaseUrl + '/' + encodeURIComponent(form) + '?popup=1';
                return;
            }

            var modal = ensureModal();
            var frame = modal.querySelector('[data-ihsan-frame]');
            frame.src = checkoutBaseUrl + '/' + encodeURIComponent(form) + '?popup=1';
            modal.style.display = 'flex';
            document.documentElement.style.overflow = 'hidden';
        },
        close: function () {
            var modal = document.getElementById(modalId);

            if (!modal) {
                return;
            }

            modal.style.display = 'none';
            modal.querySelector('[data-ihsan-frame]').src = 'about:blank';
            document.documentElement.style.overflow = '';
        }
    };

    window.addEventListener('message', function (event) {
        if (!event.data || !event.data.type) {
            return;
        }

        if (event.data.type === 'donation-popup-close') {
            window.IhsanCheckout.close();
        }

        if (event.data.type === 'ihsan:donation-success') {
            window.IhsanCheckout.close();

            setTimeout(function () {
                window.location.reload();
            }, 1200);
        }
    });

    document.addEventListener('click', function (event) {
        var trigger = event.target.closest('[data-ihsan-form], a[href*="form="]');

        if (!trigger) {
            return;
        }

        var form = trigger.getAttribute('data-ihsan-form') || formFromUrl(trigger.getAttribute('href'));

        if (!form) {
            return;
        }

        event.preventDefault();
        window.IhsanCheckout.open(form);
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            var form = formFromUrl(window.location.href);
            if (form) {
                window.IhsanCheckout.open(form);
            }
        });
    } else {
        var form = formFromUrl(window.location.href);
        if (form) {
            window.IhsanCheckout.open(form);
        }
    }
})();
JS;

        return response(str_replace(
            ['CHECKOUT_BASE_URL', 'DONATE_BASE_URL'],
            [Js::from($checkoutBaseUrl), Js::from($donateBaseUrl)],
            $script,
        ), 200)
            ->header('Content-Type', 'application/javascript; charset=UTF-8')
            ->header('Cache-Control', 'no-cache');
    }

    public function checkout(Request $request, string $form): mixed
    {
        $element = Element::query()
            ->where('is_active', true)
            ->whereHas('campaign', fn ($query) => $query
                ->where('form_parameter', $form)
                ->where('checkout_modal_enabled', true)
            )
            ->with('campaign.organization')
            ->first();

        $params = ['embed' => 1];
        if ($request->has('popup')) {
            $params['popup'] = $request->input('popup');
        }

        if ($element) {
            if (! $this->isAllowedReferer($request, data_get($element->campaign->organization, 'settings.allowed_domains', []))) {
                abort(403);
            }

            return redirect()->route('donations.show', ['element' => $element->token, ...$params]);
        }

        $campaign = Campaign::query()
            ->where('form_parameter', $form)
            ->where('checkout_modal_enabled', true)
            ->where('status', CampaignStatus::Active)
            ->with('organization')
            ->first();

        if (! $campaign) {
            abort(404);
        }

        if (! $this->isAllowedReferer($request, data_get($campaign->organization, 'settings.allowed_domains', []))) {
            abort(403);
        }

        return redirect()->route('donations.campaign-show', ['campaign' => $campaign->form_parameter, ...$params]);
    }

    /**
     * @param  array<int, string>  $allowedDomains
     */
    private function isAllowedReferer(Request $request, array $allowedDomains): bool
    {
        if ($allowedDomains === []) {
            return false;
        }

        $referer = $request->headers->get('referer');

        if (! is_string($referer) || $referer === '') {
            return false;
        }

        $host = parse_url($referer, PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return false;
        }

        $host = Str::lower($host);

        return collect($allowedDomains)
            ->filter()
            ->map(function (string $domain): string {
                $domain = Str::lower(trim($domain));

                $parsed = parse_url($domain);

                return $parsed['host'] ?? $domain;
            })
            ->contains(fn (string $domain): bool => $host === $domain || Str::endsWith($host, '.'.$domain));
    }
}
