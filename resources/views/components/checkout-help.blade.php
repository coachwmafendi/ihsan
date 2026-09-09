{{--
    The three things a donor wants to know before handing over a card, and a way
    to tell us when the checkout itself is the thing that went wrong.

    One panel serves all three, and where it opens is decided by the width of
    this row rather than the width of the window. The checkout usually runs in
    an iframe narrower than any breakpoint, so a `md:` rule reads the host
    page's width and answers for the wrong box - on a desktop screen the panel
    still opened as an accordion.

    Wide enough, and it lifts above the row as a balloon with a tail, in the
    same white the rest of the app uses for tooltips. Narrower, and it opens
    underneath instead, where a floating card would cover the form it is
    explaining.
--}}
@props(['organizationName', 'problemReportSent' => false])

<div
    x-data="{
        open: null,
        floats: false,
        toggle(key) { this.open = this.open === key ? null : key; },
        close() { this.open = null; },
        measure() { this.floats = this.$el.offsetWidth >= 460; },
        init() {
            this.measure();
            new ResizeObserver(() => this.measure()).observe(this.$el);
        },
    }"
    @keydown.escape.window="close()"
    {{ $attributes->merge(['class' => 'relative border-t border-slate-100 pt-3']) }}
>
    <div class="flex flex-wrap items-center justify-center gap-x-2 gap-y-1 text-xs text-slate-400">
        @foreach ([
            'secure' => 'Is my donation secure?',
            'cancel' => 'Can I cancel a monthly donation?',
            'problem' => 'Report a problem',
        ] as $key => $label)
            @if (! $loop->first)
                <span aria-hidden="true" class="text-slate-300">&middot;</span>
            @endif
            <button
                type="button"
                @click="toggle('{{ $key }}')"
                x-bind:aria-expanded="open === '{{ $key }}' ? 'true' : 'false'"
                aria-controls="checkout-help-panel"
                class="rounded underline-offset-2 transition hover:text-slate-700 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-600/40"
                x-bind:class="open === '{{ $key }}' ? 'text-slate-700 underline' : ''"
            >
                {{ $label }}
            </button>
        @endforeach
    </div>

    <div
        id="checkout-help-panel"
        x-show="open"
        x-cloak
        @click.outside="close()"
        class="rounded-xl bg-white p-4 text-left text-sm leading-relaxed text-slate-600"
        x-bind:class="floats
            ? 'absolute bottom-full left-1/2 z-20 mb-3 w-96 -translate-x-1/2 shadow-[0_4px_20px_rgba(15,23,42,0.22)]'
            : 'mt-3 border border-slate-200 shadow-sm'"
    >
        {{-- The tail only belongs to the floating shape; underneath the row the
             panel is attached to nothing and a tail would point at the page. --}}
        <span
            x-show="floats"
            aria-hidden="true"
            class="absolute -bottom-1 left-1/2 size-3 -translate-x-1/2 rotate-45 bg-white shadow-[3px_3px_6px_rgba(15,23,42,0.10)]"
        ></span>

        <div x-show="open === 'secure'" x-cloak class="space-y-2">
            <p class="font-semibold text-slate-900">Is my donation secure?</p>
            <p>Your card details go straight from your browser to Stripe, the company that processes the payment, over an encrypted connection. They never reach Ihsan's own servers and we never store them.</p>
            <p>Stripe handles payments for millions of businesses and is certified at the highest level of the card industry's security standard.</p>
        </div>

        <div x-show="open === 'cancel'" x-cloak class="space-y-2">
            <p class="font-semibold text-slate-900">Can I cancel a monthly donation?</p>
            <p>Whenever you like, without having to ask anyone. A monthly gift is charged on the same date each month until you stop it.</p>
            <p>Every receipt we email carries a link to your own supporter page. From there you can change the amount, pause the plan for a while, or end it altogether.</p>
        </div>

        <div x-show="open === 'problem'" x-cloak>
            @if ($problemReportSent)
                <div class="space-y-2">
                    <p class="font-semibold text-slate-900">Thank you</p>
                    <p>Your report has reached {{ $organizationName }}. If it needs an answer, they will be in touch.</p>
                </div>
            @else
                <form wire:submit="submitProblemReport" class="space-y-3">
                    <p class="font-semibold text-slate-900">Report a problem</p>
                    <p class="text-xs">Tell us which step you reached and what did not work. This goes to {{ $organizationName }}.</p>

                    <label class="block">
                        <span class="sr-only">What went wrong</span>
                        <textarea
                            wire:model="problemReport"
                            rows="4"
                            maxlength="500"
                            aria-describedby="problem-report-error"
                            @error('problemReport') aria-invalid="true" @enderror
                            class="w-full rounded-lg border border-slate-200 px-3 py-2 text-base outline-none transition focus:border-teal-600 focus:ring-2 focus:ring-teal-600/20 sm:text-sm"
                            placeholder="The card form never loaded on step 3."
                        ></textarea>
                        <div id="problem-report-error">
                            @error('problemReport')<span role="alert" class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                        </div>
                    </label>

                    {{-- A donor describing a payment that failed is exactly the
                         person likely to paste a card number into the box. --}}
                    <label class="flex items-start gap-2 text-xs">
                        <input
                            type="checkbox"
                            wire:model="problemReportConfirmed"
                            @error('problemReportConfirmed') aria-invalid="true" @enderror
                            class="mt-0.5 size-4 rounded border-slate-300 text-teal-600 focus:ring-teal-600"
                        />
                        <span>I have not included any card or personal details above.</span>
                    </label>
                    @error('problemReportConfirmed')<span role="alert" class="block text-xs text-red-600">{{ $message }}</span>@enderror

                    <div class="flex justify-end gap-2 pt-1">
                        <button type="button" @click="close()" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-50">
                            Cancel
                        </button>
                        <button type="submit" class="rounded-lg bg-teal-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-teal-700">
                            <span wire:loading.remove wire:target="submitProblemReport">Send report</span>
                            <span wire:loading wire:target="submitProblemReport">Sending...</span>
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</div>
