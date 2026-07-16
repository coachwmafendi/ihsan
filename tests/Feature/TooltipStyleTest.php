<?php

it('renders app tooltips as light balloon boxes with a tail', function () {
    $view = $this->blade('<x-ui.tooltip text="Helpful hint"><button type="button">?</button></x-ui.tooltip>');

    $view->assertSee('rounded-xl bg-white', false)
        ->assertSee('rotate-45 rounded-[2px] bg-white', false)
        ->assertSee('resolvedPosition', false)
        ->assertDontSee('bg-slate-800', false);
});

it('renders the copy button feedback in the same light style with a tail', function () {
    $view = $this->blade('<x-ui.copy-button value="ABC123" />');

    $view->assertSee('bg-white', false)
        ->assertSee('rotate-45', false)
        ->assertDontSee('bg-slate-800', false);
});
