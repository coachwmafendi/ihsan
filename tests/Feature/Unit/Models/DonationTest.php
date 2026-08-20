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

it('capitalises legacy bucket device types for display', function () {
    expect(Donation::factory()->make(['device_type' => 'mobile'])->deviceLabel())->toBe('Mobile')
        ->and(Donation::factory()->make(['device_type' => 'tablet'])->deviceLabel())->toBe('Tablet')
        ->and(Donation::factory()->make(['device_type' => 'desktop'])->deviceLabel())->toBe('Desktop');
});

it('leaves specific device types cased as they are stored', function () {
    expect(Donation::factory()->make(['device_type' => 'iPhone'])->deviceLabel())->toBe('iPhone')
        ->and(Donation::factory()->make(['device_type' => 'iPad'])->deviceLabel())->toBe('iPad')
        ->and(Donation::factory()->make(['device_type' => 'Chrome OS'])->deviceLabel())->toBe('Chrome OS')
        ->and(Donation::factory()->make(['device_type' => 'Android'])->deviceLabel())->toBe('Android');
});

it('names the operating system when only a bucket device type was captured', function () {
    expect(Donation::factory()->make(['device_type' => 'desktop', 'os' => 'macOS'])->deviceLabel())->toBe('macOS')
        ->and(Donation::factory()->make(['device_type' => 'desktop', 'os' => 'Windows'])->deviceLabel())->toBe('Windows')
        ->and(Donation::factory()->make(['device_type' => 'desktop', 'os' => 'Linux'])->deviceLabel())->toBe('Linux')
        ->and(Donation::factory()->make(['device_type' => 'mobile', 'os' => 'Android'])->deviceLabel())->toBe('Android');
});

it('keeps the bucket label when the operating system was not recognised', function () {
    expect(Donation::factory()->make(['device_type' => 'desktop', 'os' => 'Unknown'])->deviceLabel())->toBe('Desktop')
        ->and(Donation::factory()->make(['device_type' => 'desktop', 'os' => null])->deviceLabel())->toBe('Desktop');
});

it('spells a mac device type the way the operating system is named', function () {
    expect(Donation::factory()->make(['device_type' => 'Mac'])->deviceLabel())->toBe('macOS');
});

it('has no device label when no device type was captured', function () {
    expect(Donation::factory()->make(['device_type' => null])->deviceLabel())->toBeNull()
        ->and(Donation::factory()->make(['device_type' => null, 'os' => 'macOS'])->deviceLabel())->toBeNull();
});
