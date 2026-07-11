<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\OrganizationDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('soft deletes an organization without removing its files', function () {
    Storage::fake('public');

    $logo = UploadedFile::fake()->image('logo.png');
    $logoPath = $logo->store('organizations/logos', 'public');

    $documentFile = UploadedFile::fake()->create('certificate.pdf', 100);
    $documentPath = $documentFile->store('organizations/documents', 'public');

    $organization = Organization::factory()->create([
        'logo_path' => $logoPath,
    ]);

    OrganizationDocument::factory()->create([
        'organization_id' => $organization->id,
        'file_path' => $documentPath,
    ]);

    Storage::disk('public')->assertExists($logoPath);
    Storage::disk('public')->assertExists($documentPath);

    $organization->delete();

    expect($organization->fresh()?->deleted_at)->not->toBeNull();
    expect(Organization::query()->where('id', $organization->id)->exists())->toBeFalse();
    Storage::disk('public')->assertExists($logoPath);
    Storage::disk('public')->assertExists($documentPath);
});

it('removes logo and document files when an organization is force deleted', function () {
    Storage::fake('public');

    $logo = UploadedFile::fake()->image('logo.png');
    $logoPath = $logo->store('organizations/logos', 'public');

    $documentFile = UploadedFile::fake()->create('certificate.pdf', 100);
    $documentPath = $documentFile->store('organizations/documents', 'public');

    $organization = Organization::factory()->create([
        'logo_path' => $logoPath,
    ]);

    OrganizationDocument::factory()->create([
        'organization_id' => $organization->id,
        'file_path' => $documentPath,
    ]);

    Storage::disk('public')->assertExists($logoPath);
    Storage::disk('public')->assertExists($documentPath);

    $organization->forceDelete();

    expect(Organization::withTrashed()->where('id', $organization->id)->exists())->toBeFalse();
    Storage::disk('public')->assertMissing($logoPath);
    Storage::disk('public')->assertMissing($documentPath);
});
