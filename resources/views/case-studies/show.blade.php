<x-layouts::landing>
    <x-slot:title>{{ $study['title'] }} — Ihsan Case Study</x-slot:title>

    <section class="relative overflow-hidden bg-[#0f172a] px-6 py-24 lg:py-32">
        <div class="mx-auto max-w-5xl">
            <a href="/" class="mb-8 inline-flex items-center text-sm text-[#94a3b8] hover:text-white">
                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Back to home
            </a>

            <h1 class="text-4xl font-semibold tracking-tight text-white sm:text-5xl lg:text-6xl">
                {{ $study['title'] }}
            </h1>

            @if (filled($study['subtitle']))
                <p class="mt-6 max-w-2xl text-lg text-[#94a3b8] lg:text-xl">
                    {{ $study['subtitle'] }}
                </p>
            @endif
        </div>
    </section>

    <section class="border-y border-[#1e293b] bg-[#0b1120] px-6 py-12">
        <div class="mx-auto grid max-w-5xl gap-8 sm:grid-cols-3">
            @foreach ($study['stats'] as $stat)
                <div class="text-center">
                    <p class="text-3xl font-bold text-white">{{ $stat['value'] }}</p>
                    <p class="mt-1 text-sm text-[#94a3b8]">{{ $stat['label'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    <section class="px-6 py-16 lg:py-24">
        <div class="prose prose-invert prose-lg mx-auto max-w-3xl">
            {!! $study['content'] !!}
        </div>
    </section>

    <section class="bg-[#0f172a] px-6 py-16">
        <div class="mx-auto max-w-3xl rounded-2xl bg-[#1e293b] p-8 text-center lg:p-12">
            <h2 class="text-2xl font-semibold text-white">Ready to launch your campaign?</h2>
            <p class="mt-3 text-[#94a3b8]">Join organisations like {{ $study['title'] }} and start collecting donations today.</p>
            <div class="mt-8 flex flex-col items-center justify-center gap-4 sm:flex-row">
                <a href="{{ route('register.org') }}" class="inline-flex items-center justify-center rounded-lg bg-[#22c55e] px-6 py-3 font-medium text-[#0f172a] hover:bg-[#16a34a]">
                    Get started free
                </a>
                <a href="/demo/msk" class="inline-flex items-center justify-center rounded-lg border border-[#475569] px-6 py-3 font-medium text-white hover:border-[#64748b]">
                    View demo
                </a>
            </div>
        </div>
    </section>
</x-layouts::landing>
