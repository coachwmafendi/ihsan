@php
    use App\Support\EmbedWidget;

    $type = $liveType ?? ($element?->type?->value ?? null);
    $baseUrl = url('/');
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
            $staticButton = \App\Support\EmbedWidget::staticButtonHtml($element);
            $embedCode = $staticButton."\n".'<script src="'.$widgetSrc.'" data-token="'.$token.'" data-api-base="'.$baseUrl.'" data-enhance="true"></script>';
            $hasIframeFallback = false;
        } else {
            $embedCode = '<script src="'.$widgetSrc.'" data-token="'.$token.'" data-api-base="'.$baseUrl.'"></script>';
            $hasIframeFallback = in_array($cleanType, ['link'], true);
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
    <div class="col-span-full space-y-4">
        <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">
            Embed code — {{ ucwords(str_replace('_', ' ', $type)) }}
        </p>

        <div
            x-data="{ code: @js($embedCode), copied: false }"
            class="overflow-hidden rounded-xl border border-zinc-200 bg-zinc-50"
        >
            <div class="flex items-center justify-between gap-3 border-b border-zinc-200 px-4 py-2.5">
                <span class="text-xs font-medium text-zinc-500">Copy this code to your website</span>
                <button
                    type="button"
                    x-on:click="navigator.clipboard.writeText(code).then(() => { copied = true; setTimeout(() => copied = false, 2000) })"
                    class="rounded-md bg-white px-3 py-1.5 text-xs font-semibold text-zinc-700 shadow-sm ring-1 ring-zinc-200 hover:bg-zinc-100"
                >
                    <span x-show="!copied">Copy</span>
                    <span x-show="copied" x-cloak class="text-emerald-600">Copied!</span>
                </button>
            </div>
            <x-ui.tooltip text="Click to copy">
                <pre class="overflow-x-auto cursor-pointer select-all p-4 text-xs leading-relaxed text-zinc-700 transition hover:bg-zinc-100/50"
                     x-on:click="navigator.clipboard.writeText(code).then(() => { copied = true; setTimeout(() => copied = false, 2000) })"
                ><code x-text="code" class="pointer-events-none"></code></pre>
            </x-ui.tooltip>
        </div>

        @if ($iframeCode)
        <div
            x-data="{ code: @js($iframeCode), copied: false }"
            class="overflow-hidden rounded-xl border border-zinc-200 bg-zinc-50"
        >
            <div class="flex items-center justify-between gap-3 border-b border-zinc-200 px-4 py-2.5">
                <span class="text-xs font-medium text-zinc-500">WordPress / visual editor (iframe)</span>
                <button
                    type="button"
                    x-on:click="navigator.clipboard.writeText(code).then(() => { copied = true; setTimeout(() => copied = false, 2000) })"
                    class="rounded-md bg-white px-3 py-1.5 text-xs font-semibold text-zinc-700 shadow-sm ring-1 ring-zinc-200 hover:bg-zinc-100"
                >
                    <span x-show="!copied">Copy</span>
                    <span x-show="copied" x-cloak class="text-emerald-600">Copied!</span>
                </button>
            </div>
            <x-ui.tooltip text="Click to copy">
                <pre class="overflow-x-auto cursor-pointer select-all p-4 text-xs leading-relaxed text-zinc-700 transition hover:bg-zinc-100/50"
                     x-on:click="navigator.clipboard.writeText(code).then(() => { copied = true; setTimeout(() => copied = false, 2000) })"
                ><code x-text="code" class="pointer-events-none"></code></pre>
            </x-ui.tooltip>
        </div>
        @endif
    </div>
    @endif
@endif
