<?php

declare(strict_types=1);

use App\Support\ClientInfo;

it('detects an iPhone', function () {
    $agent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.0 Mobile/15E148 Safari/604.1';

    expect(ClientInfo::detectDeviceType($agent))->toBe('iPhone');
});

it('detects an iPad', function () {
    $agent = 'Mozilla/5.0 (iPad; CPU OS 16_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.0 Mobile/15E148 Safari/604.1';

    expect(ClientInfo::detectDeviceType($agent))->toBe('iPad');
});

it('detects an iPod', function () {
    $agent = 'Mozilla/5.0 (iPod touch; CPU iPhone OS 12_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/12.0 Mobile/15E148 Safari/604.1';

    expect(ClientInfo::detectDeviceType($agent))->toBe('iPod');
});

it('detects Android devices', function () {
    $agent = 'Mozilla/5.0 (Linux; Android 13; SM-G991B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/110.0.0.0 Mobile Safari/537.36';

    expect(ClientInfo::detectDeviceType($agent))->toBe('Android');
});

it('detects Android tablets as Android', function () {
    $agent = 'Mozilla/5.0 (Linux; Android 12; SM-T870) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/110.0.0.0 Safari/537.36';

    expect(ClientInfo::detectDeviceType($agent))->toBe('Android');
});

it('detects Mac desktops', function () {
    $agent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.0 Safari/605.1.15';

    expect(ClientInfo::detectDeviceType($agent))->toBe('Mac');
});

it('detects Windows desktops', function () {
    $agent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/110.0.0.0 Safari/537.36';

    expect(ClientInfo::detectDeviceType($agent))->toBe('Windows');
});

it('detects Linux desktops', function () {
    $agent = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/110.0.0.0 Safari/537.36';

    expect(ClientInfo::detectDeviceType($agent))->toBe('Linux');
});

it('detects Chrome OS', function () {
    $agent = 'Mozilla/5.0 (X11; CrOS x86_64 14541.0.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/110.0.0.0 Safari/537.36';

    expect(ClientInfo::detectDeviceType($agent))->toBe('Chrome OS');
});

it('detects generic mobile devices', function () {
    $agent = 'Mozilla/5.0 (Mobile; CustomOS) AppleWebKit/537.36 (KHTML, like Gecko) MobileBrowser/1.0';

    expect(ClientInfo::detectDeviceType($agent))->toBe('mobile');
});

it('detects generic tablets', function () {
    $agent = 'Mozilla/5.0 (Tablet; CustomOS) AppleWebKit/537.36 (KHTML, like Gecko) TabletBrowser/1.0';

    expect(ClientInfo::detectDeviceType($agent))->toBe('tablet');
});

it('falls back to desktop for unknown agents', function () {
    expect(ClientInfo::detectDeviceType('Mozilla/5.0 (compatible; SomeBot/1.0)'))->toBe('desktop');
});

it('falls back to desktop when no agent is provided', function () {
    expect(ClientInfo::detectDeviceType(null))->toBe('desktop');
});

it('prefers iPhone over Mac detection', function () {
    $agent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_0 like Mac OS X) ...';

    expect(ClientInfo::detectDeviceType($agent))->toBe('iPhone');
});
