<?php

declare(strict_types=1);

use App\Models\Donor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/**
 * A name typed with a stray space kept it. Joining first and last only trimmed
 * the ends, so "Muhammad Nurshahid " and "Ramle " read as a name with a gap in
 * the middle on every receipt, notification and list.
 */
it('squishes the space a donor typed into their own name', function () {
    $donor = Donor::factory()->create([
        'first_name' => 'Muhammad Nurshahid ',
        'last_name' => 'Ramle ',
    ]);

    $stored = DB::table('donors')->where('id', $donor->id)->first();

    expect($stored->first_name)->toBe('Muhammad Nurshahid')
        ->and($stored->last_name)->toBe('Ramle')
        ->and($stored->name)->toBe('Muhammad Nurshahid Ramle')
        ->and($donor->fresh()->name)->toBe('Muhammad Nurshahid Ramle');
});

it('squishes a run of spaces, not just one', function () {
    $donor = Donor::factory()->create([
        'first_name' => 'Iskandar  ',
        'last_name' => ' Zulkarnai ',
    ]);

    expect($donor->fresh()->name)->toBe('Iskandar Zulkarnai');
});

it('squishes a name stored without first and last names', function () {
    $donor = Donor::factory()->create([
        'first_name' => null,
        'last_name' => null,
        'name' => 'Siti  Aisha  Jantan',
    ]);

    expect($donor->fresh()->name)->toBe('Siti Aisha Jantan');
});

it('reads a name already stored with a gap as though it were clean', function () {
    // Written before names were squished on the way in, so the hook never saw
    // it. The row is dirty; what people read should not be.
    $id = DB::table('donors')->insertGetId([
        'public_id' => 'DNRTEST1',
        'first_name' => 'Mohd ',
        'last_name' => 'Shaimi ',
        'name' => 'Mohd  Shaimi',
        'email' => 'legacy-gap@example.com',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(Donor::find($id)->name)->toBe('Mohd Shaimi');
});

it('leaves a name that was already clean alone', function () {
    $donor = Donor::factory()->create([
        'first_name' => 'Nur Aisyah',
        'last_name' => 'Abdul Rahman',
    ]);

    expect($donor->fresh()->name)->toBe('Nur Aisyah Abdul Rahman');
});

it('cleans the stored columns of donors written before names were squished', function () {
    DB::table('donors')->insert([
        [
            'public_id' => 'DNRTEST2',
            'first_name' => 'Zakiah ',
            'last_name' => 'Abdullah ',
            'name' => 'Zakiah  Abdullah',
            'email' => 'legacy-one@example.com',
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'public_id' => 'DNRTEST3',
            'first_name' => 'Aminah',
            'last_name' => 'Yusof',
            'name' => 'Aminah Yusof',
            'email' => 'already-clean@example.com',
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    $exitCode = Artisan::call('app:normalize-donor-names');

    expect($exitCode)->toBe(0)
        ->and(Artisan::output())->toContain('1 donor');

    $cleaned = DB::table('donors')->where('public_id', 'DNRTEST2')->first();

    expect($cleaned->first_name)->toBe('Zakiah')
        ->and($cleaned->last_name)->toBe('Abdullah')
        ->and($cleaned->name)->toBe('Zakiah Abdullah');
});

it('changes nothing on a dry run', function () {
    DB::table('donors')->insert([
        'public_id' => 'DNRTEST4',
        'first_name' => 'Masod ',
        'last_name' => 'Akib ',
        'name' => 'Masod  Akib',
        'email' => 'dry-run@example.com',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Artisan::call('app:normalize-donor-names', ['--dry-run' => true]);

    expect(Artisan::output())->toContain('Masod  Akib')
        ->and(DB::table('donors')->where('public_id', 'DNRTEST4')->first()->name)->toBe('Masod  Akib');
});
