<?php

use App\Models\Organization;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('serves the organization logo from the public disk', function () {
    Storage::fake('public');

    $organization = Organization::factory()->create([
        'logo_path' => 'organizations/logos/test-logo.png',
    ]);

    Storage::disk('public')->put(
        'organizations/logos/test-logo.png',
        UploadedFile::fake()->image('test-logo.png', 100, 100)->get()
    );

    $response = $this->get(route('organization.logo', $organization));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('image/');
});
