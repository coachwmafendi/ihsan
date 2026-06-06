<?php

use App\Console\Commands\BackfillPublicIds;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Element;
use App\Models\MonthlyInvoice;
use App\Models\Organization;
use App\Models\Subscription;
use App\Models\User;
use App\Services\PublicIdGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('public_id model integration', function () {
    it('auto-generates public_id on create for all models', function (string $modelClass, callable $factory) {
        $model = $factory();

        expect($model->public_id)
            ->not->toBeNull()
            ->toHaveLength(8);
    })->with([
        'User' => [User::class, fn () => User::factory()->create()],
        'Campaign' => [Campaign::class, fn () => Campaign::factory()->create()],
        'Donor' => [Donor::class, fn () => Donor::factory()->create()],
        'Donation' => [Donation::class, fn () => Donation::factory()->create()],
        'Subscription' => [Subscription::class, fn () => Subscription::factory()->create()],
        'Element' => [Element::class, fn () => Element::factory()->create()],
        'MonthlyInvoice' => [MonthlyInvoice::class, fn () => MonthlyInvoice::factory()->create()],
    ]);

    it('generates correct format for each model', function (string $modelClass, string $prefix, int $expectedLength) {
        $id = PublicIdGenerator::generate($modelClass);

        expect($id)
            ->toStartWith($prefix)
            ->toHaveLength($expectedLength)
            ->toMatch('/^[A-Z1-9]+$/');
    })->with([
        'User' => [User::class, 'U', 8],
        'Campaign' => [Campaign::class, 'IH', 8],
        'Donor' => [Donor::class, 'DR', 8],
        'Donation' => [Donation::class, 'D', 8],
        'Subscription' => [Subscription::class, 'R', 8],
        'Element' => [Element::class, 'E', 8],
        'MonthlyInvoice' => [MonthlyInvoice::class, 'I', 8],
    ]);

    it('retries when collision occurs', function () {
        User::factory()->create(['public_id' => 'UAAAAAAAA']);

        $ids = [];
        for ($i = 0; $i < 20; $i++) {
            $user = User::factory()->create();
            $ids[] = $user->public_id;
        }

        expect($ids)->toHaveCount(20)
            ->and(array_unique($ids))->toHaveCount(20);
    });

    it('preserves manually assigned public_id', function () {
        $user = User::factory()->create(['public_id' => 'UCUSTOM01']);

        expect($user->public_id)->toBe('UCUSTOM01');
    });

    it('generates unique public_ids across multiple creates', function () {
        $users = User::factory()->count(50)->create();
        $ids = $users->pluck('public_id')->toArray();

        expect(array_unique($ids))->toHaveCount(50);
    });

    it('backfills public_id for existing records', function () {
        $org = Organization::factory()->create();

        $userId = User::insertGetId([
            'organization_id' => $org->id,
            'name' => 'Test User',
            'email' => 'test-backfill@example.com',
            'password' => bcrypt('password'),
            'role' => 'ngo_admin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = User::find($userId);
        expect($user->public_id)->toBeNull();

        $this->artisan(BackfillPublicIds::class)
            ->assertSuccessful();

        $user->refresh();
        expect($user->public_id)
            ->not->toBeNull()
            ->toHaveLength(8);
    });

    it('uses public_id as route key for Donor', function () {
        $donor = Donor::factory()->create();

        expect($donor->getRouteKeyName())->toBe('public_id')
            ->and($donor->getRouteKey())->toBe($donor->public_id);
    });
});
