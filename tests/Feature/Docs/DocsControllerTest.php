<?php

declare(strict_types=1);

it('renders the docs landing page', function (): void {
    $response = $this->get(route('docs.show'));

    $response->assertOk();
    $response->assertViewIs('docs.show');
    $response->assertSee('Ihsan Documentation');
});

it('renders a nested article', function (): void {
    $response = $this->get(route('docs.show', ['path' => 'build-integrate/embed-widget']));

    $response->assertOk();
    $response->assertSee('Embed widget');
});

it('extracts the page title from the first heading', function (): void {
    $response = $this->get(route('docs.show', ['path' => 'build-integrate/embed-widget']));

    $response->assertOk();
    $response->assertViewHas('title', 'Embed widget');
});

it('renders a category index when no page is provided', function (): void {
    $response = $this->get(route('docs.show', ['path' => 'getting-started']));

    $response->assertOk();
    $response->assertSee('Getting started');
});

it('returns 404 for a missing page', function (): void {
    $this->get(route('docs.show', ['path' => 'does-not-exist']))->assertNotFound();
});

it('blocks directory traversal attempts', function (string $path): void {
    $this->get(route('docs.show', ['path' => $path]))->assertNotFound();
})->with([
    '../composer.json',
    'foo/../../composer.json',
    '..%2f..%2fcomposer.json',
]);

it('renders gfm tables', function (): void {
    $response = $this->get(route('docs.show', ['path' => 'build-integrate/embed-widget']));

    $response->assertOk();
    $response->assertSee('<table', false);
});

it('marks the current page as active in the sidebar', function (): void {
    $response = $this->get(route('docs.show', ['path' => 'getting-started/installation']));

    $response->assertOk();
    $response->assertSee('aria-current="page"', false);
});
