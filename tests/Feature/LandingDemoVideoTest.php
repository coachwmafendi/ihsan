<?php

declare(strict_types=1);

/**
 * The demo video lives on YouTube, so the landing can ship before the upload
 * does. Until an ID is set there is nothing to play, and an empty frame on
 * the page would look broken.
 */
it('leaves the demo section out until a video is set', function () {
    config(['landing.demo_video_id' => null]);

    $this->get('/')
        ->assertOk()
        ->assertDontSee('id="demo"', false)
        ->assertDontSee('youtube', false);
});

it('points "how it works" at the features until a video is set', function () {
    config(['landing.demo_video_id' => null]);

    $this->get('/')
        ->assertOk()
        ->assertSee('href="#features"', false)
        ->assertDontSee('href="#demo"', false);
});

it('shows the demo section once a video is set', function () {
    config(['landing.demo_video_id' => 'dQw4w9WgXcQ']);

    $this->get('/')
        ->assertOk()
        ->assertSee('id="demo"', false)
        ->assertSee(asset('images/landing/demo-poster.jpg'), false);
});

it('points "how it works" at the demo once a video is set', function () {
    config(['landing.demo_video_id' => 'dQw4w9WgXcQ']);

    $this->get('/')
        ->assertOk()
        ->assertSee('href="#demo"', false);
});

/**
 * Embedding YouTube on load costs every visitor its player scripts and
 * cookies, whether or not they press play. The page ships a poster and only
 * builds the privacy-enhanced player after a click.
 */
it('builds the player only after the visitor presses play', function () {
    config(['landing.demo_video_id' => 'dQw4w9WgXcQ']);

    $html = $this->get('/')->assertOk()->getContent();

    preg_match('/<section id="demo".*?<\/section>/s', $html, $section);

    expect($section)->not->toBeEmpty();

    $withoutTemplates = preg_replace('/<template\b.*?<\/template>/s', '', $section[0]);

    expect($section[0])->toContain('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ?autoplay=1')
        ->and($withoutTemplates)->not->toContain('<iframe');
});

it('falls back to YouTube itself when scripts do not run', function () {
    config(['landing.demo_video_id' => 'dQw4w9WgXcQ']);

    $this->get('/')
        ->assertOk()
        ->assertSee('href="https://www.youtube.com/watch?v=dQw4w9WgXcQ"', false);
});

it('ships the poster the demo section points at', function () {
    expect(public_path('images/landing/demo-poster.jpg'))->toBeFile();
});
