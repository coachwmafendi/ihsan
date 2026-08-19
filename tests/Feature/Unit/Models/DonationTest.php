<?php

declare(strict_types=1);

use App\Models\Donation;

it('maps device types to a device category', function () {
    expect(Donation::factory()->make(['device_type' => 'iPhone'])->deviceCategory())->toBe('mobile')
        ->and(Donation::factory()->make(['device_type' => 'Android'])->deviceCategory())->toBe('mobile')
        ->and(Donation::factory()->make(['device_type' => 'mobile'])->deviceCategory())->toBe('mobile')
        ->and(Donation::factory()->make(['device_type' => 'iPad'])->deviceCategory())->toBe('tablet')
        ->and(Donation::factory()->make(['device_type' => 'tablet'])->deviceCategory())->toBe('tablet')
        ->and(Donation::factory()->make(['device_type' => 'Mac'])->deviceCategory())->toBe('desktop')
        ->and(Donation::factory()->make(['device_type' => 'Windows'])->deviceCategory())->toBe('desktop')
        ->and(Donation::factory()->make(['device_type' => 'desktop'])->deviceCategory())->toBe('desktop')
        ->and(Donation::factory()->make(['device_type' => null])->deviceCategory())->toBe('desktop');
});

it('extracts utm parameters from the stored utm_params json', function () {
    $donation = Donation::factory()->make([
        'utm_params' => [
            'utm_source' => 'fb',
            'utm_medium' => 'paid',
            'utm_campaign' => '120250810002650199',
            'utm_term' => '120250810002680199',
            'utm_content' => '120250810002670199',
        ],
    ]);

    expect($donation->utm_parameters)->toBe([
        'source' => 'fb',
        'medium' => 'paid',
        'campaign' => '120250810002650199',
        'term' => '120250810002680199',
        'content' => '120250810002670199',
    ]);
});

it('returns null utm parameters when utm_params has none', function () {
    $donation = Donation::factory()->make(['utm_params' => ['source' => 'element']]);

    expect($donation->utm_parameters)->toBe([
        'source' => null,
        'medium' => null,
        'campaign' => null,
        'term' => null,
        'content' => null,
    ]);
});

it('strips the scheme and query string from the displayed page url', function () {
    $donation = Donation::factory()->make([
        'page_url' => 'https://mtaqlaa.onpay.my/order/form/infaq-overseas?fbclid=IwcGRvZgF&utm_medium=paid&utm_source=fb',
    ]);

    expect($donation->page_url_display)->toBe('mtaqlaa.onpay.my/order/form/infaq-overseas')
        ->and($donation->page_url_query_count)->toBe(3);
});

it('keeps a relative page url intact and reports no parameters', function () {
    $donation = Donation::factory()->make([
        'page_url' => '/ms/maahad-tahfiz-development-fund-ramadan-2026/',
    ]);

    expect($donation->page_url_display)->toBe('/ms/maahad-tahfiz-development-fund-ramadan-2026/')
        ->and($donation->page_url_query_count)->toBe(0);
});

it('keeps the port when the page url carries one', function () {
    $donation = Donation::factory()->make(['page_url' => 'http://app.ihsan.test:8090/donate/1ghM0J']);

    expect($donation->page_url_display)->toBe('app.ihsan.test:8090/donate/1ghM0J');
});

it('returns no page url display when the donation has no page url', function () {
    $donation = Donation::factory()->make(['page_url' => null]);

    expect($donation->page_url_display)->toBeNull()
        ->and($donation->page_url_query_count)->toBe(0);
});
