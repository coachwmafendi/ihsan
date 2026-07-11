<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ihsan Donation Button</title>
    <style>
        * { box-sizing: border-box; }
        html, body {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            background: transparent;
            overflow: hidden;
        }
        body {
            display: flex;
            align-items: center;
            justify-content: {{ $alignment }};
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        a, button.cta {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            text-decoration: none;
            font-weight: 600;
            line-height: 1.3;
            white-space: nowrap;
            letter-spacing: 0.01em;
            color: #fff;
            background: {{ $background }};
            padding: {{ $padding }};
            font-size: {{ $fontSize }};
            border-radius: {{ $radius }};
            border: 0;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.15);
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: pointer !important;
            font-family: inherit;
        }
        a:hover, button.cta:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 18px rgba(0, 0, 0, 0.22);
        }
        a:active, button.cta:active {
            transform: scale(0.97);
        }
        a svg, button.cta svg {
            flex-shrink: 0;
        }
    </style>
</head>
<body>
    @if ($openInPopup)
        <button type="button" class="cta" data-url="{{ $url }}" onclick='openCheckout(event, @js($url))'>
            {!! $iconSvg !!}
            <span>{{ $text }}</span>
        </button>
        <script>
            function openCheckout(event, url) {
                event.preventDefault();
                if (!window.parent || window.parent === window) {
                    fallbackCheckout(url);
                    return;
                }
                var handled = false;
                function onAck(e) {
                    if (e.data && e.data.type === 'ihsan:checkout-ack') {
                        handled = true;
                        window.removeEventListener('message', onAck);
                    }
                }
                window.addEventListener('message', onAck);
                window.parent.postMessage({ type: 'ihsan:open-checkout', url: url }, '*');
                setTimeout(function () {
                    window.removeEventListener('message', onAck);
                    if (!handled) { fallbackCheckout(url); }
                }, 250);
            }
            function fallbackCheckout(url) {
                var w = 560, h = 760;
                var l = Math.round((window.screen.width - w) / 2);
                var t = Math.round((window.screen.height - h) / 2);
                window.open(url, 'ihsan-donate', 'width=' + w + ',height=' + h + ',left=' + l + ',top=' + t + ',scrollbars=yes,resizable=yes,noopener,noreferrer');
            }
        </script>
    @else
        <a href="{{ $url }}" target="_blank" rel="noopener">
            {!! $iconSvg !!}
            <span>{{ $text }}</span>
        </a>
    @endif
</body>
</html>
