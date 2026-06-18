<?php

namespace App\Mail\Concerns;

use App\Models\Donor;

trait SetsDonorLocale
{
    protected function donorLocale(?Donor $donor): string
    {
        return $donor?->locale ?? config('app.locale');
    }
}
