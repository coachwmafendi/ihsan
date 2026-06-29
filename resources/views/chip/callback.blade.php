<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CHIP Payment {{ ucfirst($status) }}</title>
</head>
<body>
    <script>
        (function () {
            const status = @js($status);
            const donationId = @js($donation->public_id);
            const finalizeUrl = @js(route('chip.finalize', ['donation' => $donation->public_id]));
            const campaignUrl = @js($campaignUrl);
            const returnTo = @js($returnTo ?? null);
            const normalizedStatus = status === 'success' ? 'success' : (status === 'failure' ? 'failure' : 'cancelled');
            const inIframe = window.self !== window.top;
            const hasOpener = window.opener && window.opener !== window;
            const hasParentContext = inIframe || hasOpener;

            function notifyOpener() {
                const target = window.opener || window.parent;

                if (! target || target === window) {
                    return false;
                }

                const messageType = status === 'success'
                    ? 'chip:payment:success'
                    : (status === 'failure' ? 'chip:payment:failure' : 'chip:payment:cancel');

                target.postMessage({
                    type: messageType,
                    donationId: donationId,
                }, '*');

                return true;
            }

            function broadcastResult() {
                const messageType = status === 'success'
                    ? 'chip:payment:success'
                    : (status === 'failure' ? 'chip:payment:failure' : 'chip:payment:cancel');

                const payload = {
                    type: messageType,
                    donationId: donationId,
                    _ts: Date.now(),
                };

                if (typeof BroadcastChannel !== 'undefined') {
                    try {
                        const channel = new BroadcastChannel('ihsan:chip:' + donationId);
                        channel.postMessage(payload);
                        channel.close();
                    } catch (e) {
                        // Some environments block BroadcastChannel; fall through to localStorage.
                    }
                }

                try {
                    localStorage.setItem('ihsan:chip:' + donationId, JSON.stringify(payload));
                    localStorage.removeItem('ihsan:chip:' + donationId);
                } catch (e) {
                    // localStorage may be unavailable in private/third-party contexts.
                }
            }

            function buildReturnUrl(baseUrl) {
                if (! baseUrl || typeof baseUrl !== 'string') {
                    return null;
                }

                const separator = baseUrl.includes('?') ? '&' : '?';

                return baseUrl + separator + 'chip_status=' + encodeURIComponent(normalizedStatus) + '&donation_id=' + encodeURIComponent(donationId);
            }

            function tryClosePopup() {
                window.close();

                // Some browsers block window.close() even for script-opened popups.
                // If the popup is still here, redirect to the originating donation form
                // so the user always lands on a useful screen.
                setTimeout(function () {
                    if (! window.closed) {
                        const redirectUrl = buildReturnUrl(returnTo);

                        if (redirectUrl) {
                            window.location.href = redirectUrl;
                        }
                    }
                }, 800);
            }

            function showIframeResult() {
                // The parent window controls the main donation UI. Just show a short
                // confirmation in the iframe and then clear it so it does not keep
                // rendering the CHIP page or a duplicate donation form.
                const message = status === 'success'
                    ? 'Completing your donation...'
                    : ('Payment ' + normalizedStatus + '. Returning...');

                document.body.innerHTML = '<p style="font-family:sans-serif;text-align:center;padding:40px;margin:0;">' + message + '</p>';

                setTimeout(function () {
                    try {
                        window.location.replace('about:blank');
                    } catch (e) {
                        // Fallback if replacing the iframe location is not allowed.
                    }
                }, 1200);
            }

            function finish() {
                // Always broadcast the result through BroadcastChannel / localStorage
                // so the originating page can react even if window.opener was lost.
                broadcastResult();

                // If we are embedded in an iframe, never take over the whole window or
                // call the finalize endpoint. The parent is listening for the result.
                if (inIframe) {
                    notifyOpener();
                    showIframeResult();
                    return;
                }

                // If this is a script-opened popup, notify the opener and close.
                if (hasOpener) {
                    notifyOpener();
                    tryClosePopup();
                    return;
                }

                // Top-level tab with no opener (e.g. popup blocker). Redirect back to
                // the originating donation form so it can display the result state.
                const redirectUrl = buildReturnUrl(returnTo);

                if (redirectUrl) {
                    window.location.href = redirectUrl;
                    return;
                }

                // Final fallback to the campaign page when no return URL was provided.
                if (status === 'success') {
                    window.location.href = campaignUrl;
                    return;
                }

                document.body.innerHTML = '<p style="font-family:sans-serif;text-align:center;padding:40px;">Payment ' + status + '. <a href="' + campaignUrl + '">Return to campaign</a></p>';
            }

            // Only call the finalize endpoint when there is no parent/opener context
            // to finalize on our behalf. In those cases the originating page already
            // listens for postMessage/BroadcastChannel/localStorage events.
            if (status === 'success' && finalizeUrl && ! hasParentContext) {
                fetch(finalizeUrl, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                })
                    .catch(function () {})
                    .finally(function () {
                        setTimeout(finish, 250);
                    });
            } else {
                setTimeout(finish, 250);
            }
        })();
    </script>
    <p>Payment {{ $status }}. You may close this window.</p>
</body>
</html>
