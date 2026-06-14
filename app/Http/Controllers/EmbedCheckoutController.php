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
        modal.innerHTML = '<div style="position:relative;width:min(100%,520px);height:min(94vh,820px);background:#fff;border-radius:18px;box-shadow:0 24px 80px rgba(15,23,42,.28);overflow:hidden;"><button type="button" data-ihsan-close style="position:absolute;top:10px;right:10px;z-index:2;width:34px;height:34px;border:0;border-radius:999px;background:rgba(15,23,42,.08);font:24px/1 system-ui,sans-serif;cursor:pointer;">&times;</button><iframe title="Ihsan checkout" data-ihsan-frame style="width:100%;height:100%;border:0;" allow="payment *"></iframe></div>';

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
                window.location.href = donateBaseUrl + '/' + encodeURIComponent(form);
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
        if (event.data && event.data.type === 'donation-popup-close') {
            window.IhsanCheckout.close();
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
