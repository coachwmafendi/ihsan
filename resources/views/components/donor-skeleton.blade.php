<div class="donor-wrap" x-data="{ loaded: false }" x-init="$nextTick(() => setTimeout(() => loaded = true, 400))">
    <div class="donor-skeleton" x-show="!loaded" x-transition.opacity.duration.250ms x-cloak aria-hidden="true">
        {{ $skeleton }}
    </div>
    <div x-show="loaded" x-transition.opacity.duration.250ms>
        {{ $slot }}
    </div>
</div>
