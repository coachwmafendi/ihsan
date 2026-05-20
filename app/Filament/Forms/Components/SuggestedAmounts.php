<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\Field;

class SuggestedAmounts extends Field
{
    protected string $view = 'filament.forms.components.suggested-amounts';

    protected function setUp(): void
    {
        parent::setUp();

        $this->default([
            'one_time' => [
                ['amount' => '200', 'label' => ''],
                ['amount' => '100', 'label' => ''],
                ['amount' => '50', 'label' => ''],
                ['amount' => '30', 'label' => ''],
                ['amount' => '10', 'label' => ''],
                ['amount' => '5', 'label' => ''],
            ],
            'monthly' => [
                ['amount' => '200', 'label' => ''],
                ['amount' => '100', 'label' => ''],
                ['amount' => '50', 'label' => ''],
                ['amount' => '30', 'label' => ''],
                ['amount' => '10', 'label' => ''],
                ['amount' => '5', 'label' => ''],
            ],
            'impact_enabled' => false,
            'default_monthly' => '25',
        ]);
    }
}
