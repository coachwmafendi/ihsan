<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\OrganizationDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrganizationDocument>
 */
class OrganizationDocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'document_type' => 'registration_cert',
            'file_path' => 'organization-documents/'.fake()->uuid().'.pdf',
            'original_filename' => 'registration-certificate.pdf',
        ];
    }
}
