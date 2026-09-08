{{--
    A side panel that stays in view while the page scrolls.

    Pinned at a fixed offset, a panel taller than the window keeps its bottom
    below the fold for as long as the page is scrolled, so those items can never
    be read. Capping its height instead gives it a scrollbar of its own, which
    puts the same items behind a second scroll gesture.

    So the offset follows the panel's height: while it fits, it sits below the
    top of the window as usual; when it does not, it is pinned by its bottom
    edge instead, and the page carries it up until everything has been seen.
--}}
@props(['offset' => 24])

<div
    x-data="{
        stickyTop: '{{ $offset }}px',
        measure() {
            const overflow = this.$el.offsetHeight + {{ $offset }} - window.innerHeight;

            this.stickyTop = overflow > 0
                ? `${-overflow}px`
                : '{{ $offset }}px';
        },
        init() {
            this.measure();

            // The panel changes height on its own - a modal opens, a status note
            // appears - so watch it rather than only the window.
            new ResizeObserver(() => this.measure()).observe(this.$el);
            window.addEventListener('resize', () => this.measure());
        },
    }"
    x-bind:style="`top: ${stickyTop}`"
    {{ $attributes->merge(['class' => 'lg:sticky lg:self-start']) }}
>
    {{ $slot }}
</div>
