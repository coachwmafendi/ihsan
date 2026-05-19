<div
    x-data="{
        copied: false,
        copyText: @js($url ?? ''),
        doCopy() {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(this.copyText).then(() => {
                    this.copied = true;
                    setTimeout(() => this.copied = false, 2000);
                });
            } else {
                const textarea = document.createElement('textarea');
                textarea.value = this.copyText;
                textarea.style.position = 'fixed';
                textarea.style.opacity = '0';
                document.body.appendChild(textarea);
                textarea.select();
                try {
                    document.execCommand('copy');
                    this.copied = true;
                    setTimeout(() => this.copied = false, 2000);
                } catch (err) {
                    console.error('Copy failed', err);
                }
                document.body.removeChild(textarea);
            }
        }
    }"
    class="col-span-full"
>
    <label class="mb-1.5 block text-sm font-medium text-zinc-700">HTML Code</label>
    <div class="flex overflow-hidden rounded-xl border border-zinc-300 bg-zinc-100 shadow-sm">
        <code class="flex-1 truncate px-4 py-3 font-mono text-sm text-zinc-700">{{ $url ?? 'Available after saving this element.' }}</code>
        <button
            type="button"
            x-on:click="doCopy()"
            class="flex items-center gap-1.5 border-l border-zinc-300 bg-white px-4 py-3 text-sm font-semibold text-zinc-700 transition hover:bg-zinc-50 hover:text-zinc-900 active:bg-zinc-100"
        >
            <svg class="size-4 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0 0 13.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 0 1-.75.75H9.75a.75.75 0 0 1-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 0 1-2.25 2.25H6.75A2.25 2.25 0 0 1 4.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 0 1 1.927-.184" />
            </svg>
            <span x-show="!copied">Copy</span>
            <span x-show="copied" x-cloak class="text-emerald-600">Copied!</span>
        </button>
    </div>
    <p class="mt-1.5 text-xs text-zinc-500">Share this link to let donors access your form directly.</p>
</div>
