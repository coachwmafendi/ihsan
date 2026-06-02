<?php

namespace App\Console\Commands;

use App\Enums\ElementType;
use App\Models\Element;
use App\Models\Organization;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CreateDonorPortalButtons extends Command
{
    protected $signature = 'elements:create-donor-portal-buttons';

    protected $description = 'Create donor portal button elements for all orgs that do not have one';

    public function handle(): void
    {
        $orgs = Organization::query()
            ->whereNotExists(function ($query) {
                $query->selectRaw(1)
                    ->from('elements')
                    ->whereColumn('elements.organization_id', 'organizations.id')
                    ->where('elements.is_donor_portal_default', true);
            })
            ->whereHas('campaigns')
            ->get();

        if ($orgs->isEmpty()) {
            $this->info('All organizations already have a donor portal button.');

            return;
        }

        $created = 0;

        foreach ($orgs as $org) {
            $campaign = $org->campaigns()->first();

            if ($campaign === null) {
                continue;
            }

            Element::create([
                'organization_id' => $org->getKey(),
                'campaign_id' => $campaign->getKey(),
                'name' => 'Dedicated Donorportal Button',
                'token' => Str::random(6),
                'type' => ElementType::Button,
                'config' => [
                    'button_text' => 'Make a new donation',
                    'button_color' => 'bg-blue-600 hover:bg-blue-700',
                    'button_size' => 'text-base px-6 py-3',
                    'corner_radius' => 8,
                    'button_effect' => 'none',
                    'action' => 'checkout_modal',
                ],
                'is_active' => true,
                'is_donor_portal_default' => true,
            ]);

            $created++;
            $this->info("Created donor portal button for: {$org->name}");
        }

        $this->newLine();
        $this->info("Done. Created {$created} donor portal button(s).");
    }
}
