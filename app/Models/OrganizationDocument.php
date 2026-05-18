<?php

namespace App\Models;

use Database\Factories\OrganizationDocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
