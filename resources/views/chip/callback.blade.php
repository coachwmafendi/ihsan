<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payment status — Ihsan</title>
    <style>
        body { font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f8fafc; color: #334155; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 20px; text-align: center; }
        .box { background: #fff; border-radius: 16px; padding: 32px; box-shadow: 0 10px 40px rgba(15,23,42,.1); max-width: 420px; width: 100%; }
        .title { font-size: 1.25rem; font-weight: 700; margin: 16px 0 8px; }
        .text { font-size: .9375rem; line-height: 1.5; color: #64748b; }
    </style>
</head>
<body>
    <div class="box">
        <div class="status-icon" id="status-icon">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#0d9488" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-6.219-8.56"/><path d="M9 12l2 2 4-4"/></svg>
        </div>
        <h1 class="title">Finalising your payment</h1>
        <p class="text">Please wait while we confirm the payment with CHIP.</p>
    </div>

    <script>
        (function () {
            var inIframe = window.parent !== window;
            var donationId = @json($donationPublicId);
            var status = @json($status);
            var returnTo = @json($returnTo);

            function finish(success) {
                if (inIframe) {
                    window.parent.postMessage({
                        type: 'ihsan:chip-success',
                        donationPublicId: donationId,
                        success: success,
                    }, '*');
                    return;
                }

                if (returnTo) {
                    var separator = returnTo.indexOf('?') !== -1 ? '&' : '?';
                    window.top.location.href = returnTo + separator + 'chip_' + (success ? 'success' : 'failure') + '=1&donation=' + encodeURIComponent(donationId);
                }
            }

            if (!donationId) {
                finish(false);
                return;
            }

            fetch('/chip/confirm/' + encodeURIComponent(donationId), {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': @json(csrf_token()),
                },
            })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                finish(data.success && status === 'success');
            })
            .catch(function () {
                finish(false);
            });
        })();
    </script>
</body>
</html>
