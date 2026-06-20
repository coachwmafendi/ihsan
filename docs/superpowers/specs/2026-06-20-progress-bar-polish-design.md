# Campaign Progress Bar UI Polish — Design

## Context
File: `resources/views/livewire/campaign-public-page.blade.php`

The campaign public page currently displays a raised-vs-goal card with:
- Raised and goal amounts.
- A single continuous progress bar with percentage tooltip above it.
- White divider lines at 10%, 25%, 50%, 100%.
- Checkpoint dots (completed = emerald, current = pulsing emerald, pending = slate).
- Checkpoint labels.
- A next-milestone / goal-reached callout box.

For the live campaign at `/campaigns/IHL31PJI`, the campaign has exceeded its goal, so the bar is at 100% and the callout shows “Goal reached!”.

## Goal
Make the progress section feel more celebratory and motivating (Option A: Confetti Milestone Bar), especially for campaigns that have hit or exceeded their target, while keeping it usable at lower progress percentages.

## Proposed Design

### Visual Direction
- Keep the existing card layout and `rounded-xl` container.
- Keep percentage label above the bar but make it bolder and use a slight scale/glow near 100%.
- Add subtle sparkle icons (✨) above filled checkpoint positions (10%, 25%, 50%) to reward progress so far.
- Place a finish flag / trophy icon (🏁 / 🏆) at the goal end once progress is complete.
- Replace the flat emerald fill with a gradient (`from-emerald-400 to-emerald-600`) for more energy.
- Make the current checkpoint dot pulse with a ring glow.
- Rewrite the callout for the goal-exceeded state to emphasise ongoing momentum (e.g. “Goal smashed!” + “Every extra ringgit expands the impact.”).

### Interaction / Animation
- Progress bar width animates from 0 to target percentage on first render (`transition-all duration-1000 ease-out`).
- Sparkle icons fade in with a staggered delay after the bar animation completes.
- Percentage label slides with the bar during animation.
- When `progressPercent >= 100`, a tiny confetti burst is optional (CSS-only or very small JS) but **not required for MVP**.

### Content Changes
Current callout:
> 🎉 Goal reached!  
> Thank you for helping us hit the target.

Proposed callout when goal is reached or exceeded:
> 🎉 Goal smashed!  
> We crossed the target. Let’s keep the momentum going!

For non-exceeded campaigns keep the existing “Next milestone: …” copy.

### Accessibility
- Sparkle / finish icons are decorative; hide from screen readers with `aria-hidden="true"`.
- Keep meaningful text (raised, goal, percentage, callout) outside decorative elements.
- Maintain sufficient colour contrast on all text.
- Respect `prefers-reduced-motion`: disable the pulse animation and bar slide for users who prefer reduced motion.

### Mobile Considerations
- Sparkles remain small and do not overlap labels.
- Checkpoint labels can still wrap/truncate gracefully on narrow screens.
- The percentage tooltip must not clip outside the viewport when progress is near 100%.

## Files to Modify
- `resources/views/livewire/campaign-public-page.blade.php` — main progress section markup and styles.
- Optional: extend `config` JSON in `campaigns` table or Campaign settings UI to allow custom callout copy.

## Testing
- Manual visual check at 0%, 25%, 50%, 75%, 100%, and >100% progress.
- Mobile viewport check.
- Verify no console errors.
- Pint formatting after PHP changes.

## Out of Scope
- Full confetti canvas/JS library.
- New database migrations unless callout customization is requested separately.
