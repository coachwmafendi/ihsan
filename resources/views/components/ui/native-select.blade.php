{{-- resources/views/components/ui/native-select.blade.php --}}
{{-- A plain <select> with room for its arrow. The browser draws its own chevron
     hard against the right edge, inside whatever padding the field has, so it
     is hidden and drawn here inside a padding that leaves it space. Callers
     style the field as usual but set only left padding (pl-*). --}}
@props(['wrapperClass' => null])

<div @class(['relative', $wrapperClass])>
    <select {{ $attributes->class(['block w-full appearance-none pr-10']) }}>
        {{ $slot }}
    </select>
    <x-heroicon-m-chevron-down class="pointer-events-none absolute inset-y-0 right-3 my-auto size-4 text-slate-400" aria-hidden="true" />
</div>
