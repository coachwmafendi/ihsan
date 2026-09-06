<?php

declare(strict_types=1);

namespace App\Rules;

use App\Enums\CampaignStatus;
use App\Models\Campaign;
use App\Models\Element;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Reject a name that another live record in the same organization already uses.
 *
 * Two campaigns sharing a title are indistinguishable in the campaign list, in
 * reports and in the element picker, so the organiser cannot tell which one the
 * money landed in. The comparison ignores case and padding because "Qurban" and
 * " qurban " read as the same name to everyone but the database.
 */
final class UniqueNameWithinOrganization implements ValidationRule
{
    /**
     * @param  Builder<covariant \Illuminate\Database\Eloquent\Model>  $query  Scoped to the organization and to records that are still live.
     */
    public function __construct(
        private Builder $query,
        private string $column,
        private string $message,
        private ?int $ignoreId = null,
    ) {}

    /**
     * Archived campaigns are out of the way, so their titles are free again.
     */
    public static function forCampaignTitle(?int $organizationId, ?int $ignoreId = null): self
    {
        return new self(
            query: Campaign::query()
                ->where('organization_id', $organizationId)
                ->where('status', '!=', CampaignStatus::Archived),
            column: 'title',
            message: 'Another campaign in your organization already uses this title.',
            ignoreId: $ignoreId,
        );
    }

    public static function forElementName(?int $organizationId, ?int $ignoreId = null): self
    {
        return new self(
            query: Element::query()
                ->where('organization_id', $organizationId)
                ->whereNull('archived_at'),
            column: 'name',
            message: 'Another element in your organization already uses this name.',
            ignoreId: $ignoreId,
        );
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || trim($value) === '') {
            return;
        }

        $query = (clone $this->query)
            ->whereRaw("LOWER(TRIM({$this->column})) = ?", [Str::lower(trim($value))]);

        if ($this->ignoreId !== null) {
            $query->whereKeyNot($this->ignoreId);
        }

        if ($query->exists()) {
            $fail($this->message);
        }
    }
}
