@php
    use App\Support\EmbedWidget;

    $type = $record?->type?->value;
    $token = $record?->token;
    $baseUrl = config('app.url');
    $widgetSrc = EmbedWidget::scriptUrl();

    if (! $record || ! $type || ! $token) return;

    $fullCode = '<script src="'.$widgetSrc.'" data-token="'.$token.'" data-api-base="'.$baseUrl.'"></script>';
    $shortCode = '<script src="/e/widget.js?v='.EmbedWidget::version().'" data-token="'.$token.'"></script>';
@endphp

<div class="flex items-center gap-1" style="max-width:260px;overflow:hidden">
    <x-ui.tooltip :text="$fullCode">
        <code class="min-w-0 flex-1 truncate text-[10px] leading-relaxed text-zinc-500 font-mono bg-zinc-100 rounded px-1.5 py-1 select-all"
            data-full-code="{{ $fullCode }}"
            onclick="event.preventDefault();event.stopPropagation();navigator.clipboard.writeText(this.dataset.fullCode).then(function(){var e=this.parentElement.querySelector('.copy-trigger');var s=e.querySelector('svg');var t=e.querySelector('.copied-text');s.classList.add('hidden');t.classList.remove('hidden');setTimeout(function(){s.classList.remove('hidden');t.classList.add('hidden')},1000)}.bind(this))"
        >{{ $shortCode }}</code>
    </x-ui.tooltip>
    <x-ui.tooltip text="Copy embed code">
        <span
            class="copy-trigger shrink-0 rounded p-1 text-zinc-400 hover:text-zinc-700 hover:bg-zinc-100 transition cursor-pointer"
            onclick="event.preventDefault();event.stopPropagation();navigator.clipboard.writeText(this.parentElement.querySelector('code').dataset.fullCode).then(function(){var s=this.querySelector('svg');var t=this.querySelector('.copied-text');s.classList.add('hidden');t.classList.remove('hidden');setTimeout(function(){s.classList.remove('hidden');t.classList.add('hidden')},1000)}.bind(this))"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
            <span class="copied-text hidden text-emerald-600 text-[11px] font-medium">Copied!</span>
        </span>
    </x-ui.tooltip>
</div>
