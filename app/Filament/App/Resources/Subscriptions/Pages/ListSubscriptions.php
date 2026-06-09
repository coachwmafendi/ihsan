<?php

namespace App\Filament\App\Resources\Subscriptions\Pages;

use App\Filament\App\Resources\Subscriptions\SubscriptionResource;
use App\Models\Subscription;
use Filament\Resources\Pages\ListRecords;

class ListSubscriptions extends ListRecords
{
    protected static string $resource = SubscriptionResource::class;

    protected string $view = 'filament.app.resources.subscriptions.pages.list-subscriptions';

    protected static ?string $title = 'Recurring plans';

    public function hasSubscriptions(): bool
    {
        return Subscription::whereHas('campaign', fn ($q) => $q->where('organization_id', auth()->user()->organization_id))->exists();
    }
}
