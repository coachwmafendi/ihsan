{{--
    Stripe.js, loaded without holding up the page.

    A plain script tag in the head stops the parser until a round trip to
    js.stripe.com finishes, and everything after it — the stylesheet, Livewire,
    Alpine — waits its turn. On a phone on mobile data that is most of the time
    the donor spends looking at the skeleton loader.

    Loading it async hands that time back, at the cost of no longer knowing
    `Stripe` is there by the time a component initialises. The promise below is
    that guarantee: await it before the first `Stripe(...)` call. It settles
    either way, so a blocked or failed script surfaces as the form's own
    "payment system failed to initialize" message rather than a silent hang.
--}}
<link rel="preconnect" href="https://js.stripe.com" crossorigin>

<script>
    window.ihsanStripeJs = new Promise(function (resolve, reject) {
        window.ihsanStripeJsSettle = { resolve: resolve, reject: reject };
    });

    // Nothing awaits this on a page that never reaches the payment step, and
    // an unhandled rejection there would only be noise in the console.
    window.ihsanStripeJs.catch(function () {});
</script>

<script
    src="https://js.stripe.com/v3/"
    async
    onload="window.ihsanStripeJsSettle.resolve(window.Stripe)"
    onerror="window.ihsanStripeJsSettle.reject(new Error('Stripe.js failed to load'))"
></script>
