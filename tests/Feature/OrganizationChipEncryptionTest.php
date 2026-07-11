<?php

declare(strict_types=1);

use App\Models\Organization;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

it('encrypts chip secrets at rest and decrypts on access', function () {
    $organization = Organization::factory()->create([
        'chip_api_key' => 'secret-api-key',
        'chip_webhook_public_key' => '-----BEGIN PUBLIC KEY-----\nMIIB...\n-----END PUBLIC KEY-----',
    ]);

    $rawApiKey = DB::table('organizations')->where('id', $organization->id)->value('chip_api_key');
    $rawPublicKey = DB::table('organizations')->where('id', $organization->id)->value('chip_webhook_public_key');

    expect($rawApiKey)->not->toBe('secret-api-key');
    expect(Crypt::decryptString($rawApiKey))->toBe('secret-api-key');

    expect($rawPublicKey)->not->toBe('-----BEGIN PUBLIC KEY-----\nMIIB...\n-----END PUBLIC KEY-----');
    expect(Crypt::decryptString($rawPublicKey))->toBe('-----BEGIN PUBLIC KEY-----\nMIIB...\n-----END PUBLIC KEY-----');

    $organization->refresh();

    expect($organization->chip_api_key)->toBe('secret-api-key');
    expect($organization->chipApiKey())->toBe('secret-api-key');
    expect($organization->chip_webhook_public_key)->toBe('-----BEGIN PUBLIC KEY-----\nMIIB...\n-----END PUBLIC KEY-----');
});
