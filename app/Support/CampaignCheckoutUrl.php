<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\ElementType;
use App\Models\Campaign;
use App\Models\Element;

/**
 * A checkout url for a campaign that will actually open.
 *
 * The campaign route is guarded: it answers 404 unless the organisation turned
 * the checkout modal on. A recovery email sent a donor straight into that 404,
 * because the link was built from the campaign without asking whether the
 * campaign would accept it.
 *
 * An element is the safer address - one exists for every campaign anybody has
 * embedded anywhere, and it is guarded only on being active.
 */
class CampaignCheckoutUrl
{
    /**
     * @param  array<string, mixed>  $query
     */
    public static function for(?Campaign $campaign, array $query = []): ?string
    {
        if ($campaign === null || ! $campaign->exists) {
            return null;
        }

        $element = self::preferredElement($campaign);

        if ($element !== null) {
            return route('donations.show', ['element' => $element, ...$query]);
        }

        if ($campaign->checkout_modal_enabled) {
            return route('donations.campaign-show', ['campaign' => $campaign, ...$query]);
        }

        return null;
    }

    /**
     * The element the organisation nominated for the donor portal, else the
     * first of the kinds that open a full checkout on their own.
     */
    private static function preferredElement(Campaign $campaign): ?Element
    {
        $elements = Element::query()
            ->where('campaign_id', $campaign->getKey())
            ->where('is_active', true)
            ->get();

        if ($elements->isEmpty()) {
            return null;
        }

        $portalDefault = $elements->firstWhere('is_donor_portal_default', true);

        if ($portalDefault !== null) {
            return $portalDefault;
        }

        foreach ([
            ElementType::Form,
            ElementType::Button,
            ElementType::Popup,
            ElementType::FloatingButton,
            ElementType::StickyButton,
            ElementType::QrCode,
            ElementType::Link,
        ] as $type) {
            $match = $elements->firstWhere('type', $type);

            if ($match !== null) {
                return $match;
            }
        }

        return null;
    }
}
