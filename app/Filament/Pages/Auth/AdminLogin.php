<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login;
use Illuminate\Contracts\Support\Htmlable;

class AdminLogin extends Login
{
    protected string $view = 'filament.admin.pages.auth.login';

    protected static string $layout = 'filament-panels::components.layout.base';

    public function getHeading(): string|Htmlable|null
    {
        return null;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return null;
    }
}
