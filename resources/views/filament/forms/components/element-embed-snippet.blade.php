@php
    use App\Support\EmbedWidget;

    $type = $liveType ?? ($element?->type?->value ?? null);
    $baseUrl = rtrim(config('app.url'), '/');
    $widgetSrc = EmbedWidget::scriptUrl();
    $token = $element?->token;
@endphp

@if ($element && $type && $token)
    @php
        $cleanType = match ($type) {
            'floating_button' => 'floating_button',
            'qr_code' => 'qr_code',
            'link' => 'link',
            default => $type,
        };

        if ($cleanType === 'button') {
            $staticMarkup = \App\Support\EmbedWidget::staticButtonHtml($element);
            $embedCode = $staticMarkup."\n".'<script src="'.$widgetSrc.'" data-token="'.$token.'" data-api-base="'.$baseUrl.'" data-enhance="true"></script>';
            $hasIframeFallback = false;
        } elseif ($cleanType === 'link') {
            $staticMarkup = \App\Support\EmbedWidget::staticLinkHtml($element);
            $embedCode = $staticMarkup."\n".'<script src="'.$widgetSrc.'" data-token="'.$token.'" data-api-base="'.$baseUrl.'" data-enhance="true"></script>';
            $hasIframeFallback = false;
        } elseif ($cleanType === 'floating_button') {
            $staticMarkup = \App\Support\EmbedWidget::staticFloatingButtonPlaceholderHtml($element);
            $embedCode = $staticMarkup."\n".'<script src="'.$widgetSrc.'" data-token="'.$token.'" data-api-base="'.$baseUrl.'" data-enhance="true"></script>';
            $hasIframeFallback = false;
        } elseif ($cleanType === 'qr_code') {
            $staticMarkup = \App\Support\EmbedWidget::staticQrCodeHtml($element);
            $embedCode = $staticMarkup."\n".'<script src="'.$widgetSrc.'" data-token="'.$token.'" data-api-base="'.$baseUrl.'" data-enhance="true"></script>';
            $hasIframeFallback = false;
        } else {
            $embedCode = '<script src="'.$widgetSrc.'" data-token="'.$token.'" data-api-base="'.$baseUrl.'"></script>';
            $hasIframeFallback = false;
        }

        $listenerScript = <<<'JS'
<script>
(function () {
  if (window.__ihsanModalInstalled) return;
  window.__ihsanModalInstalled = true;
  function openModal(url) {
    var existing = document.getElementById('ihsan-modal');
    if (existing) existing.remove();
    var modal = document.createElement('div');
    modal.id = 'ihsan-modal';
    modal.setAttribute('role', 'dialog');
    modal.setAttribute('aria-modal', 'true');
    modal.style.cssText = 'position:fixed;inset:0;z-index:2147483647;display:flex;align-items:center;justify-content:center;background:rgba(15,23,42,.58);padding:20px;';
    modal.innerHTML = '<div style="position:relative;width:min(100%,520px);height:min(94vh,820px);background:#fff;border-radius:18px;box-shadow:0 24px 80px rgba(15,23,42,.28);overflow:hidden;"><button type="button" data-ihsan-close style="position:absolute;top:10px;right:10px;z-index:2;width:34px;height:34px;border:0;border-radius:999px;background:rgba(15,23,42,.08);font:24px/1 system-ui,sans-serif;cursor:pointer;">&times;</button><iframe title="Ihsan checkout" data-ihsan-frame src="' + url + '" style="width:100%;height:100%;border:0;"></iframe></div>';
    modal.addEventListener('click', function (event) { if (event.target === modal || event.target.closest('[data-ihsan-close]')) closeModal(); });
    document.addEventListener('keydown', function (event) { if (event.key === 'Escape') closeModal(); });
    document.body.appendChild(modal);
    document.documentElement.style.overflow = 'hidden';
  }
  function closeModal() {
    var modal = document.getElementById('ihsan-modal');
    if (!modal) return;
    modal.remove();
    document.documentElement.style.overflow = '';
  }
  window.addEventListener('message', function (event) {
    if (!event.data || typeof event.data !== 'object') return;
    if (event.data.type === 'ihsan:open-checkout') { openModal(event.data.url); if (event.source) { event.source.postMessage({ type: 'ihsan:checkout-ack' }, '*'); } }
    if (event.data.type === 'donation-popup-close') closeModal();
    if (event.data.type === 'ihsan:donation-success') { closeModal(); setTimeout(function () { window.location.reload(); }, 1200); }
  });
})();
</script>
JS;

        $iframeCode = $hasIframeFallback
            ? '<iframe src="'.$baseUrl.'/e/button/'.$token.'" width="100%" height="70" frameborder="0" scrolling="no" style="border:0;overflow:hidden;"></iframe>' . "\n" . $listenerScript
            : null;
    @endphp

    @if ($embedCode)
    <style>
        .ihsan-embed-code-card {
            width: 100%;
            border-radius: 12px;
            border: 1px solid #27272a;
            background: #18181b;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
        }
        .ihsan-embed-code-card + .ihsan-embed-code-card {
            margin-top: 1.5rem;
        }
        .ihsan-embed-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            border-bottom: 1px solid #27272a;
            padding: 0.75rem 1rem;
        }
        .ihsan-embed-title {
            color: #fafafa;
            font-size: 0.875rem;
            font-weight: 500;
            line-height: 1.25rem;
        }
        .ihsan-embed-copy {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem;
            margin: -0.25rem;
            border-radius: 8px;
            color: #a1a1aa;
            background: transparent;
            border: 0;
            cursor: pointer;
            transition: color 150ms ease, background-color 150ms ease;
        }
        .ihsan-embed-copy:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.08);
        }
        .ihsan-embed-body {
            background: #09090b;
            padding: 1rem;
        }
        .ihsan-embed-body pre {
            margin: 0;
            max-height: 200px;
            overflow-y: auto;
            white-space: pre-wrap;
            word-break: break-word;
            color: #d4d4d8;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            font-size: 0.8125rem;
            line-height: 1.625;
            scrollbar-width: thin;
            scrollbar-color: #3f3f46 #09090b;
        }
        .ihsan-embed-body pre::-webkit-scrollbar {
            width: 8px;
        }
        .ihsan-embed-body pre::-webkit-scrollbar-track {
            background: #09090b;
            border-radius: 4px;
        }
        .ihsan-embed-body pre::-webkit-scrollbar-thumb {
            background: #3f3f46;
            border-radius: 4px;
        }
        .ihsan-embed-body pre::-webkit-scrollbar-thumb:hover {
            background: #52525b;
        }
        .ihsan-embed-body pre code {
            display: block;
        }
        .ihsan-embed-copied {
            color: #22c55e;
        }
    </style>

    <div class="col-span-full" wire:key="embed-snippet-{{ md5($embedCode) }}">
        <div
            x-data="{ copied: false, copy() { navigator.clipboard.writeText(document.getElementById(@js('embed-code-'.$token)).textContent).then(() => { this.copied = true; setTimeout(() => this.copied = false, 2000) }) } }"
            class="ihsan-embed-code-card"
        >
            <div class="ihsan-embed-header">
                <span class="ihsan-embed-title">Code</span>
                <button
                    type="button"
                    x-on:click="copy()"
                    class="ihsan-embed-copy"
                    title="Copy to clipboard"
                >
                    <span x-show="!copied" class="block">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:1.25rem;height:1.25rem;">
                            <rect x="9" y="9" width="13" height="13" rx="2" ry="2" stroke="currentColor" stroke-width="2" fill="none" />
                            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </span>
                    <span x-show="copied" x-cloak class="block ihsan-embed-copied">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:1.25rem;height:1.25rem;">
                            <polyline points="20 6 9 17 4 12" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </span>
                </button>
            </div>
            <div class="ihsan-embed-body">
                <pre id="embed-code-{{ $token }}"><code>{{ $embedCode }}</code></pre>
            </div>
        </div>

        @if ($iframeCode)
        <div
            x-data="{ copied: false, copy() { navigator.clipboard.writeText(document.getElementById(@js('iframe-code-'.$token)).textContent).then(() => { this.copied = true; setTimeout(() => this.copied = false, 2000) }) } }"
            class="ihsan-embed-code-card"
            wire:key="iframe-snippet-{{ md5($iframeCode) }}"
        >
            <div class="ihsan-embed-header">
                <span class="ihsan-embed-title">WordPress / visual editor (iframe)</span>
                <button
                    type="button"
                    x-on:click="copy()"
                    class="ihsan-embed-copy"
                    title="Copy to clipboard"
                >
                    <span x-show="!copied" class="block">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:1.25rem;height:1.25rem;">
                            <rect x="9" y="9" width="13" height="13" rx="2" ry="2" stroke="currentColor" stroke-width="2" fill="none" />
                            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </span>
                    <span x-show="copied" x-cloak class="block ihsan-embed-copied">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:1.25rem;height:1.25rem;">
                            <polyline points="20 6 9 17 4 12" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </span>
                </button>
            </div>
            <div class="ihsan-embed-body">
                <pre id="iframe-code-{{ $token }}"><code>{{ $iframeCode }}</code></pre>
            </div>
        </div>
        @endif
    </div>
    @endif
@endif
