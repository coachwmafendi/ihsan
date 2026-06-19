<main class="max-w-6xl mx-auto px-4 py-12">
    <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
        {{-- Left column: campaign details --}}
        <div class="space-y-6">
            <div>
                <p class="text-sm font-medium text-slate-500">{{ $campaign->organization->name }}</p>
                <h1 class="mt-1 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">
                    {{ $campaign->title }}
                </h1>
            </div>

            @if ($campaign->image_path)
                <img
                    src="{{ \Illuminate\Support\Facades\Storage::url($campaign->image_path) }}"
                    alt=""
                    class="w-full rounded-xl object-cover mb-4"
                />
            @endif

            @if ($campaign->description)
                <div class="prose prose-slate max-w-none text-base/7 text-slate-600">
                    {!! nl2br(e($campaign->description)) !!}
                </div>
            @endif

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Raised</p>
                <p class="mt-1 text-2xl font-bold text-slate-950">
                    RM {{ number_format($campaign->collected_amount, 2) }}
                </p>

                @if ($campaign->has_target && $campaign->target_amount)
                    @php
                        $progressPercent = min(100, $campaign->target_amount > 0 ? ($campaign->collected_amount / $campaign->target_amount * 100) : 0);
                    @endphp

                    <p class="mt-1 text-sm text-slate-500">
                        of RM {{ number_format($campaign->target_amount, 2) }} goal
                    </p>
                    <div class="mt-3 h-3 rounded-full bg-slate-200">
                        <div class="h-3 rounded-full bg-teal-600" style="width: {{ $progressPercent }}%"></div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Right column: donation form --}}
        <div class="lg:pl-4">
            @livewire('donation-form', ['campaign' => $campaign, 'isPublicPage' => true], key('donation-form-'.$campaign->public_id))
        </div>
    </div>
</main>
