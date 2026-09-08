{{--
    A side panel that stays in view while the page scrolls.

    Two things decide where it can sit. The panel's own height: taller than the
    space available, and a fixed offset keeps its last items below the fold for
    as long as the page is scrolled. And the app's own header, which is sticky
    and 64px tall - an offset measured from the top of the window puts the first
    items behind it, which is what happened here twice.

    So the header is measured rather than assumed, and the offset follows the
    panel's height: while it fits, it sits just below the header; when it does
    not, it is pinned by its bottom edge instead and the page carries it up
    until everything has been seen.
--}}
@props(['gap' => 24])

<div
    x-data="{
        stickyTop: '0px',
        headerHeight() {
            // Measured, not assumed: the header's height is a layout decision
            // made elsewhere and has no business being repeated here.
            const header = document.querySelector('header.sticky');

            return header ? header.offsetHeight : 0;
        },
        measure() {
            const header = this.headerHeight();
            const available = window.innerHeight - header - {{ $gap }} * 2;

            this.stickyTop = this.$el.offsetHeight <= available
                ? `${header + {{ $gap }}}px`
                : `${window.innerHeight - this.$el.offsetHeight - {{ $gap }}}px`;
        },
        init() {
            this.measure();

            // The panel changes height on its own - a status note appears, an
            // action is added when the plan's state changes - so watch it
            // rather than only the window.
            new ResizeObserver(() => this.measure()).observe(this.$el);
            window.addEventListener('resize', () => this.measure());
        },
    }"
    x-bind:style="`top: ${stickyTop}`"
    {{ $attributes->merge(['class' => 'lg:sticky lg:self-start']) }}
>
    {{ $slot }}
</div>
