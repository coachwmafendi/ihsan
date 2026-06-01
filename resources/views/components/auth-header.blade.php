@props([
    'title',
    'description' => null,
])

<div class="flex flex-col gap-2">
    <h1 class="text-[28px] font-semibold leading-tight tracking-tight text-neutral-900">{{ $title }}</h1>
    @if ($description)
        <p class="text-[15px] text-neutral-500">{{ $description }}</p>
    @endif
</div>
