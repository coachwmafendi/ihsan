<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\OrganizationDocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $organization_id
 * @property string $document_type
 * @property string $file_path
 * @property string $original_filename
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Organization $organization
 *
 * @method static \Database\Factories\OrganizationDocumentFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrganizationDocument newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrganizationDocument newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrganizationDocument query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrganizationDocument whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrganizationDocument whereDocumentType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrganizationDocument whereFilePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrganizationDocument whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrganizationDocument whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrganizationDocument whereOriginalFilename($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrganizationDocument whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
#[Fillable(['organization_id', 'document_type', 'file_path', 'original_filename'])]
class OrganizationDocument extends Model
{
    /** @use HasFactory<OrganizationDocumentFactory> */
    use HasFactory;

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
