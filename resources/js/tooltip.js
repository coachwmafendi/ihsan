// Shared Alpine tooltip data provider. Used by resources/js/app.js and the
// Filament admin layout so tooltips behave consistently across the app.
document.addEventListener('alpine:init', () => {
    Alpine.data('uiTooltip', ({ position = 'top', align = 'center', delay = 75, disabled = false } = {}) => ({
        open: false,
        timer: null,
        tipId: 'tooltip-' + Math.random().toString(36).slice(2, 9),
        triggerEl: null,
        style: {},
        disabled,

        init() {
            if (! this.$refs.trigger) {
                return;
            }

            this.triggerEl = this.$refs.trigger;

            this.hideOnResize = () => { if (this.open) this.hide(); };
            this.hideOnScroll = () => { if (this.open) this.hide(); };
            window.addEventListener('resize', this.hideOnResize);
            window.addEventListener('scroll', this.hideOnScroll, true);
        },

        destroy() {
            if (this.hideOnResize) window.removeEventListener('resize', this.hideOnResize);
            if (this.hideOnScroll) window.removeEventListener('scroll', this.hideOnScroll, true);
            this.hide();
        },

        canShow() {
            const text = this.$el.dataset.tooltipText ?? '';
            return ! this.disabled && text.length > 0;
        },

        show() {
            if (! this.canShow()) return;

            if (this.timer) clearTimeout(this.timer);

            this.timer = setTimeout(() => {
                if (! this.canShow()) return;
                this.open = true;
                this.$nextTick(() => this.reposition());
            }, delay);
        },

        hide() {
            if (this.timer) clearTimeout(this.timer);
            this.timer = null;
            this.open = false;
        },

        reposition() {
            if (! this.triggerEl) return;

            const trigger = this.triggerEl.getBoundingClientRect();
            const tooltip = this.$refs.tooltip?.getBoundingClientRect();

            if (! tooltip || ! trigger.width || ! trigger.height) return;

            const viewport = { w: window.innerWidth, h: window.innerHeight };
            const GAP = 8;
            let pos = position;
            let align_ = align;

            const space = {
                top: trigger.top - GAP,
                bottom: viewport.h - trigger.bottom - GAP,
                left: trigger.left - GAP,
                right: viewport.w - trigger.right - GAP,
            };

            if (pos === 'top' && tooltip.height > space.top && tooltip.height < space.bottom) {
                pos = 'bottom';
            } else if (pos === 'bottom' && tooltip.height > space.bottom && tooltip.height < space.top) {
                pos = 'top';
            } else if (pos === 'left' && tooltip.width > space.left && tooltip.width < space.right) {
                pos = 'right';
            } else if (pos === 'right' && tooltip.width > space.right && tooltip.width < space.left) {
                pos = 'left';
            }

            let top, left;

            if (pos === 'top' || pos === 'bottom') {
                top = pos === 'top'
                    ? trigger.top - tooltip.height - GAP
                    : trigger.bottom + GAP;

                top = Math.max(GAP, Math.min(top, viewport.h - tooltip.height - GAP));

                if (align_ === 'start') {
                    left = trigger.left;
                } else if (align_ === 'end') {
                    left = trigger.right - tooltip.width;
                } else {
                    left = trigger.left + (trigger.width - tooltip.width) / 2;
                }

                left = Math.max(GAP, Math.min(left, viewport.w - tooltip.width - GAP));
            } else {
                left = pos === 'left'
                    ? trigger.left - tooltip.width - GAP
                    : trigger.right + GAP;

                left = Math.max(GAP, Math.min(left, viewport.w - tooltip.width - GAP));

                if (align_ === 'start') {
                    top = trigger.top;
                } else if (align_ === 'end') {
                    top = trigger.bottom - tooltip.height;
                } else {
                    top = trigger.top + (trigger.height - tooltip.height) / 2;
                }

                top = Math.max(GAP, Math.min(top, viewport.h - tooltip.height - GAP));
            }

            this.style = { top: `${top}px`, left: `${left}px` };
        },
    }));
});
