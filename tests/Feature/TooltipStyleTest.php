<?php

it('renders app tooltips as light balloon boxes', function () {
    $view = $this->blade('<x-ui.tooltip text="Helpful hint"><button type="button">?</button></x-ui.tooltip>');

    $view->assertSee('rounded-xl bg-white', false)
        ->assertSee('ring-1 ring-slate-900/10', false)
        ->assertDontSee('bg-slate-800', false);
});

it('renders the copy button feedback in the same light style', function () {
    $view = $this->blade('<x-ui.copy-button value="ABC123" />');

    $view->assertSee('bg-white', false)
        ->assertDontSee('bg-slate-800', false);
});
