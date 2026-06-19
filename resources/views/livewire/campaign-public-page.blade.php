@php
$config = $campaign->config ?? [];
$showTotalRaised = $config['show_total_raised'] ?? true;
$contentLogo = $config['content_logo'] ?? $campaign->organization->logo_path;
$contentImage = $config['content_image'] ?? $campaign->image_path;
$contentTitle = $config['content_title'] ?? $campaign->title;
$contentMessage = $config['content_message'] ?? $campaign->description;
$messageText = strip_tags((string) $contentMessage);
$messageIsLong = mb_strlen($messageText) > 200;
$messageShort = Illuminate\Support\Str::limit($messageText, 200);
@endphp

<main class="max-w-6xl mx-auto px-4 py-12">
    <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
        {{-- Left column: campaign details --}}
        <div class="space-y-6">
            <div class="flex items-center gap-3">
                @if ($contentLogo)
                    <img
                        src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($contentLogo) }}"
                        alt="{{ $campaign->organization->name }}"
                        class="h-10 object-contain"
                    />
                @endif
                <p class="text-sm font-medium text-slate-500">{{ $campaign->organization->name }}</p>
            </div>

            <div>
                <h1 class="text-xl font-bold tracking-tight text-slate-950 sm:text-2xl">
                    {{ $contentTitle }}
                </h1>
            </div>

            @if ($showTotalRaised)
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-medium text-slate-500">Raised</p>
                    <p class="mt-0.5 text-xl font-bold text-slate-950">
                        RM {{ number_format($campaign->collected_amount, 2) }}
                    </p>

                    @if ($campaign->has_target && $campaign->target_amount)
                        @php
                            $progressPercent = min(100, $campaign->target_amount > 0 ? ($campaign->collected_amount / $campaign->target_amount * 100) : 0);
                        @endphp

                        <p class="mt-0.5 text-xs text-slate-500">
                            of RM {{ number_format($campaign->target_amount, 2) }} goal
                        </p>
                        <div class="mt-2 h-2 rounded-full bg-slate-200">
                            <div class="h-2 rounded-full bg-teal-600" style="width: {{ $progressPercent }}%"></div>
                        </div>
                    @endif

                    @if ($campaign->has_end_date && $campaign->end_date)
                        <p class="mt-2 text-xs text-slate-500">
                            Campaign ends on {{ $campaign->end_date->format('M j, Y') }}
                        </p>
                    @endif
                </div>
            @endif

            @if ($contentImage)
                <img
                    src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($contentImage) }}"
                    alt=""
                    class="w-full max-h-[28rem] rounded-xl object-cover"
                />
            @endif

            @if ($contentMessage)
                <div x-data="{ expanded: false }" class="text-base/7 text-slate-600">
                    <div x-show="! expanded">
                        <p>{{ $messageShort }}</p>
                        @if ($messageIsLong)
                            <button
                                type="button"
                                x-on:click="expanded = true"
                                class="mt-2 text-sm font-semibold text-teal-700 hover:text-teal-800"
                            >
                                Read more &darr;
                            </button>
                        @endif
                    </div>
                    <div x-show="expanded" x-cloak>
                        {!! nl2br(e($contentMessage)) !!}
                        <button
                            type="button"
                            x-on:click="expanded = false"
                            class="mt-2 block text-sm font-semibold text-teal-700 hover:text-teal-800"
                        >
                            Read less &uarr;
                        </button>
                    </div>
                </div>
            @endif
        </div>

        {{-- Right column: donation form --}}
        <div class="lg:pl-4">
            @livewire('donation-form', ['campaign' => $campaign, 'isPublicPage' => true], key('donation-form-'.$campaign->public_id))
        </div>
    </div>
</main>
