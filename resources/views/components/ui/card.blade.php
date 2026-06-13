@props([
    'title' => null,
    'description' => null,
    'actions' => null,
])

<div {{ $attributes->merge(['class' => 'rounded-xl border border-slate-200 bg-white']) }}>
    @if(isset($title) || isset($actions))
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
            <div>
                @if(isset($title))
                    <h3 class="font-semibold text-slate-900">{{ $title }}</h3>
                @endif
                @if(isset($description))
                    <p class="text-sm text-slate-500 mt-0.5">{{ $description }}</p>
                @endif
            </div>
            @if(isset($actions))
                <div class="flex items-center gap-2">{{ $actions }}</div>
            @endif
        </div>
    @endif

    <div class="p-5">
        {{ $slot }}
    </div>

    @if(isset($footer))
        <div class="px-5 py-3 border-t border-slate-100">
            {{ $footer }}
        </div>
    @endif
</div>
