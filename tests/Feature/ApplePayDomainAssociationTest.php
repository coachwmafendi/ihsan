<?php

declare(strict_types=1);

/**
 * Safari validates Apple Pay against the domain running the payment session,
 * and our checkout runs in an iframe served from the panel domain. That domain
 * has to serve Stripe's association file or Safari refuses the wallet - which
 * it did, on every organisation at once, while Stripe's own dashboard still
 * reported the domain as active.
 */
it('serves the Apple Pay domain association file Safari asks for', function () {
    $path = public_path('.well-known/apple-developer-merchantid-domain-association');

    expect(file_exists($path))->toBeTrue();

    $contents = trim((string) file_get_contents($path));

    // Apple's file is hex-encoded JSON naming the payment service provider.
    $payload = json_decode((string) hex2bin($contents), true);

    expect($payload)->toBeArray()
        ->and($payload['pspId'] ?? null)->toStartWith('979C9E843F41140DF1D18442292217410450D1C9')
        ->and($payload['version'] ?? null)->toBe(1);
});

it('does not let application routing swallow the well-known path', function () {
    // A catch-all route or a redirect here would answer Safari with HTML and
    // the wallet would stay hidden with nothing in our own logs.
    $response = $this->get('/.well-known/apple-developer-merchantid-domain-association');

    expect($response->status())->not->toBe(302);
    expect($response->status())->not->toBe(500);
});
