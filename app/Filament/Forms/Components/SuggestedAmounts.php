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
            'myr' => [
                'one_time' => [
                    ['amount' => '30', 'label' => ''],
                    ['amount' => '50', 'label' => ''],
                    ['amount' => '100', 'label' => ''],
                    ['amount' => '200', 'label' => ''],
                    ['amount' => '500', 'label' => ''],
                    ['amount' => '1000', 'label' => ''],
                ],
                'monthly' => [
                    ['amount' => '200', 'label' => ''],
                    ['amount' => '100', 'label' => ''],
                    ['amount' => '50', 'label' => ''],
                    ['amount' => '30', 'label' => ''],
                    ['amount' => '10', 'label' => ''],
                    ['amount' => '5', 'label' => ''],
                ],
                'default_monthly' => '25',
            ],
            'usd' => [
                'one_time' => [
                    ['amount' => '10', 'label' => ''],
                    ['amount' => '20', 'label' => ''],
                    ['amount' => '50', 'label' => ''],
                    ['amount' => '100', 'label' => ''],
                    ['amount' => '250', 'label' => ''],
                    ['amount' => '500', 'label' => ''],
                ],
                'monthly' => [
                    ['amount' => '50', 'label' => ''],
                    ['amount' => '25', 'label' => ''],
                    ['amount' => '10', 'label' => ''],
                    ['amount' => '5', 'label' => ''],
                    ['amount' => '2', 'label' => ''],
                    ['amount' => '1', 'label' => ''],
                ],
                'default_monthly' => '10',
            ],
            'sgd' => [
                'one_time' => [
                    ['amount' => '10', 'label' => ''],
                    ['amount' => '20', 'label' => ''],
                    ['amount' => '50', 'label' => ''],
                    ['amount' => '100', 'label' => ''],
                    ['amount' => '250', 'label' => ''],
                    ['amount' => '500', 'label' => ''],
                ],
                'monthly' => [
                    ['amount' => '50', 'label' => ''],
                    ['amount' => '25', 'label' => ''],
                    ['amount' => '10', 'label' => ''],
                    ['amount' => '5', 'label' => ''],
                    ['amount' => '2', 'label' => ''],
                    ['amount' => '1', 'label' => ''],
                ],
                'default_monthly' => '10',
            ],
            'impact_enabled' => false,
        ]);

        $this->afterStateHydrated(function (SuggestedAmounts $component, $state) {
            if (! is_array($state)) {
                return;
            }

            // If old format {one_time: [...], monthly: [...]} — wrap into myr key
            if (isset($state['one_time']) && ! isset($state['myr'])) {
                $state = [
                    'myr' => [
                        'one_time' => $state['one_time'],
                        'monthly' => $state['monthly'],
                        'default_monthly' => $state['default_monthly'] ?? '25',
                    ],
                    'usd' => null,
                    'sgd' => null,
                    'impact_enabled' => $state['impact_enabled'] ?? false,
                ];
                $component->state($state);

                return;
            }

            // If legacy flat array — wrap into myr key
            if (! isset($state['myr'])) {
                $flatAmounts = array_filter(array_values($state), fn ($v) => is_numeric($v) && $v > 0);
                if (! empty($flatAmounts)) {
                    $oneTime = array_map(fn ($v) => ['amount' => (string) $v, 'label' => ''], $flatAmounts);
                    $state = [
                        'myr' => [
                            'one_time' => $oneTime,
                            'monthly' => $oneTime,
                            'default_monthly' => '25',
                        ],
                        'usd' => null,
                        'sgd' => null,
                        'impact_enabled' => false,
                    ];
                    $component->state($state);
                }
            }
        });
    }
}
