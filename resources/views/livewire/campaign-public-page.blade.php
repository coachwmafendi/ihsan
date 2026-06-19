@php
$showTotalRaised = $campaign->config['show_total_raised'] ?? true;
$contentLogo = $campaign->config['content_logo'] ?? $campaign->organization->logo_path;
$contentImage = $campaign->config['content_image'] ?? $campaign->image_path;
$contentTitle = $campaign->config['content_title'] ?? $campaign->title;
$contentMessage = $campaign->config['content_message'] ?? $campaign->description;
@endphp

<main class="max-w-6xl mx-auto px-4 py-12">
    <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
        {{-- Left column: campaign details --}}
        <div class="space-y-6">
            @if ($contentLogo)
                <img
                    src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($contentLogo) }}"
                    alt="{{ $campaign->organization->name }}"
                    class="h-16 object-contain"
                />
            @endif

            <div>
                <p class="text-sm font-medium text-slate-500">{{ $campaign->organization->name }}</p>
                <h1 class="mt-1 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">
                    {{ $contentTitle }}
                </h1>
            </div>

            @if ($contentImage)
                <img
                    src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($contentImage) }}"
                    alt=""
                    class="w-full rounded-xl object-cover"
                />
            @endif

            @if ($contentMessage)
                <div class="prose prose-slate max-w-none text-base/7 text-slate-600">
                    {!! nl2br(e($contentMessage)) !!}
                </div>
            @endif

            @if ($showTotalRaised)
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

                    @if ($campaign->has_end_date && $campaign->end_date)
                        <p class="mt-3 text-sm text-slate-500">
                            Campaign ends on {{ $campaign->end_date->format('M j, Y') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        {{-- Right column: donation form --}}
        <div class="lg:pl-4">
            @livewire('donation-form', ['campaign' => $campaign, 'isPublicPage' => true], key('donation-form-'.$campaign->public_id))
        </div>
    </div>
</main>
