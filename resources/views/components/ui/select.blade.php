{{-- resources/views/components/ui/select.blade.php --}}
<flux:select
    {{ $attributes->merge([
        'class' => 'rounded-lg border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-500',
    ]) }}
>
    {{ $slot }}
</flux:select>
