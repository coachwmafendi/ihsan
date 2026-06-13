<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\AppPanelProvider;
use App\Providers\FortifyServiceProvider;

return [
    AppServiceProvider::class,
    AdminPanelProvider::class,
    // AppPanelProvider::class, // Migrated to Livewire — see app/Livewire/App/
    FortifyServiceProvider::class,
];
