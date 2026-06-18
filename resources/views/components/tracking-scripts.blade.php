@props(['organization' => null, 'configs' => null])

@php
    $tracking = $configs ?? ($organization ? \App\Services\TrackingScriptService::config($organization) : []);
    $meta = $tracking['meta'] ?? null;
    $ga4 = $tracking['ga4'] ?? null;
    $googleAds = $tracking['google_ads'] ?? null;
    $tiktok = $tracking['tiktok'] ?? null;
    $needsGtag = ($ga4 && $ga4['enabled']) || ($googleAds && $googleAds['enabled']);
    $googleAdsSendTo = $googleAds ? (($googleAds['conversion_id'] ?? '').'/'.($googleAds['conversion_label'] ?? '')) : null;
    $linkedIn = $tracking['linkedin'] ?? null;
    $xAds = $tracking['x_ads'] ?? null;
    $snapchat = $tracking['snapchat'] ?? null;
    $hasTrackers = $meta || $needsGtag || $tiktok || $linkedIn || $xAds || $snapchat;
@endphp

@if ($hasTrackers)
    <script>
        window.__IHSAN_TRACKING__ = @js($tracking);
    </script>

    @if ($meta)
        <script>
            !function(f,b,e,v,n,t,s){
                if(f.fbq)return;n=f.fbq=function(){n.callMethod?
                n.callMethod.apply(n,arguments):n.queue.push(arguments)};
                if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
                n.queue=[];t=b.createElement(e);t.async=!0;
                t.src=v;s=b.getElementsByTagName(e)[0];
                s.parentNode.insertBefore(t,s)
            }(window, document, 'script', 'https://connect.facebook.net/en_US/fbevents.js');

            fbq('init', @js($meta['pixel_id']));

            @if ($meta['options']['track_page_views'] ?? true)
                fbq('track', 'PageView');
            @endif
        </script>

        <noscript>
            <img height="1" width="1" style="display:none"
                 src="https://www.facebook.com/tr?id={{ urlencode($meta['pixel_id']) }}&ev=PageView&noscript=1" />
        </noscript>
    @endif

    @if ($linkedIn)
        <script type="text/javascript">
            _linkedin_partner_id = @js($linkedIn['partner_id']);
            window._linkedin_data_partner_ids = window._linkedin_data_partner_ids || [];
            window._linkedin_data_partner_ids.push(_linkedin_partner_id);
            (function(l) {
                if (!l) {
                    window.lintrk = function(a, b) { window.lintrk.q.push([a, b]); };
                    window.lintrk.q = [];
                }
                var s = document.getElementsByTagName("script")[0];
                var b = document.createElement("script");
                b.type = "text/javascript";
                b.async = true;
                b.src = "https://snap.licdn.com/li.lms-analytics/insight.min.js";
                s.parentNode.insertBefore(b, s);
            })(window.lintrk);
        </script>

        @if ($linkedIn['options']['track_page_views'] ?? true)
            <noscript>
                <img height="1" width="1" style="display:none;" alt="" src="https://px.ads.linkedin.com/collect/?pid={{ urlencode($linkedIn['partner_id']) }}&fmt=gif" />
            </noscript>
        @endif
    @endif

    @if ($needsGtag)
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ urlencode($ga4['measurement_id'] ?? $googleAds['conversion_id'] ?? '') }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());

            @if ($ga4 && ($ga4['options']['track_page_views'] ?? true))
                gtag('config', @js($ga4['measurement_id']));
            @elseif ($ga4)
                gtag('config', @js($ga4['measurement_id']), { send_page_view: false });
            @endif

            @if ($googleAds)
                gtag('config', @js($googleAds['conversion_id']));
            @endif
        </script>
    @endif

    @if ($tiktok)
        <script>
            !function (w, d, t) {
                w.TiktokAnalyticsObject = t;
                var ttq = w[t] = w[t] || [];
                ttq.methods = ["page","track","identify","instances","debug","on","off","once","ready","alias","group","enableCookie","disableCookie","holdConsent","revokeConsent","grantConsent"];
                ttq.setAndDefer = function(t, e) { t[e] = function() { t.push([e].concat(Array.prototype.slice.call(arguments, 0))); }; };
                for (var i = 0; i < ttq.methods.length; i++) {
                    ttq.setAndDefer(ttq, ttq.methods[i]);
                }
                ttq.instance = function(t) {
                    for (var e = ttq._i[t] || [], n = 0; n < ttq.methods.length; n++) {
                        ttq.setAndDefer(e, ttq.methods[n]);
                    }
                    return e;
                };
                ttq.load = function(e, n) {
                    var i = "https://analytics.tiktok.com/i18n/pixel/sdk.js?sdkid=" + e + "&q=" + t;
                    var o = document.getElementsByTagName("script")[0];
                    var a = document.createElement("script");
                    a.type = "text/javascript";
                    a.async = !0;
                    a.src = i;
                    o.parentNode.insertBefore(a, o);
                };
                ttq.load(@js($tiktok['pixel_id']));
                @if ($tiktok['options']['track_page_views'] ?? true)
                    ttq.page();
                @endif
            }(window, document, 'ttq');
        </script>
    @endif

    <script>
        window.IhsanTrack = function(eventName, payload, opts) {
            var tracking = window.__IHSAN_TRACKING__ || {};
            var eventId = opts && (opts.eventID || opts.event_id) ? (opts.eventID || opts.event_id) : null;

            // Meta Pixel
            if (typeof fbq === 'function' && tracking.meta && tracking.meta.enabled) {
                var metaOptionMap = {
                    'InitiateCheckout': 'track_donation_starts',
                    'Purchase': 'track_successful_donations',
                };
                var metaOptionKey = metaOptionMap[eventName];
                if (!metaOptionKey || tracking.meta.options[metaOptionKey] !== false) {
                    var metaArgs = ['track', eventName];
                    if (payload) metaArgs.push(payload);
                    if (opts) metaArgs.push(opts);
                    fbq.apply(null, metaArgs);
                }
            }

            // Google Analytics 4
            if (typeof gtag === 'function' && tracking.ga4 && tracking.ga4.enabled) {
                var ga4Payload = payload ? Object.assign({}, payload) : {};
                if (eventId) ga4Payload.event_id = eventId;

                if (eventName === 'InitiateCheckout' && tracking.ga4.options.track_checkout_starts !== false) {
                    gtag('event', 'begin_checkout', ga4Payload);
                }

                if (eventName === 'Purchase' && tracking.ga4.options.track_purchases !== false) {
                    gtag('event', 'purchase', ga4Payload);
                }
            }

            // Google Ads Conversion
            if (typeof gtag === 'function' && tracking.google_ads && tracking.google_ads.enabled && eventName === 'Purchase') {
                if (tracking.google_ads.options.track_conversions !== false) {
                    gtag('event', 'conversion', {
                        send_to: {!! \Illuminate\Support\Js::from($googleAdsSendTo, JSON_UNESCAPED_SLASHES) !!},
                        value: payload ? payload.value : undefined,
                        currency: payload ? payload.currency : undefined,
                        transaction_id: eventId,
                    });
                }
            }

            // TikTok Pixel
            if (typeof ttq === 'function' && tracking.tiktok && tracking.tiktok.enabled) {
                if (tracking.tiktok.options.track_donation_events !== false) {
                    var ttPayload = payload ? { value: payload.value, currency: payload.currency } : {};

                    if (eventName === 'InitiateCheckout') {
                        ttq.track('InitiateCheckout', ttPayload);
                    }

                    if (eventName === 'Purchase') {
                        ttq.track('CompletePayment', ttPayload);
                    }
                }
            }

            // LinkedIn Insight Tag
            if (typeof lintrk === 'function' && tracking.linkedin && tracking.linkedin.enabled) {
                var linkedInConversionId = tracking.linkedin.conversion_id;

                if (linkedInConversionId) {
                    if (eventName === 'InitiateCheckout' && tracking.linkedin.options?.track_donation_starts !== false) {
                        lintrk('track', { conversion_id: linkedInConversionId });
                    }

                    if (eventName === 'Purchase' && tracking.linkedin.options?.track_conversions !== false) {
                        lintrk('track', { conversion_id: linkedInConversionId, value: payload ? payload.value : undefined, currency: payload ? payload.currency : undefined });
                    }
                }
            }
        };
    </script>
@else
    <script>
        window.__IHSAN_TRACKING__ = @js($tracking);
        window.IhsanTrack = function() {};
    </script>
@endif
