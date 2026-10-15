<?php

declare(strict_types=1);

use App\Models\Organization;

it('takes the first letter of the first two words, in capitals', function (string $name, string $initials) {
    expect((new Organization(['name' => $name]))->initials())->toBe($initials);
})->with([
    'title case' => ['Masjid Tahfiz Al Ayubi', 'MT'],
    'shouted' => ['MASJID TAHFIZ AL AYUBI', 'MT'],
    'lower case' => ['pertubuhan kebajikan islam', 'PK'],
    'one word' => ['Ihsan', 'I'],
    'stray spaces' => ['  Madrasah   Darul Falah ', 'MD'],
]);
