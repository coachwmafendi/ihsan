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
            'impact_enabled' => false,
            'default_monthly' => '25',
        ]);

        $this->afterStateHydrated(function (SuggestedAmounts $component, $state) {
            if (! is_array($state)) {
                return;
            }

            $needsNormalization = false;

            if (isset($state['one_time']) && isset($state['monthly'])) {
                if (! is_array($state['one_time']) || ! is_array($state['monthly'])) {
                    $needsNormalization = true;
                } else {
                    $oneTimeKeys = array_keys($state['one_time']);
                    $monthlyKeys = array_keys($state['monthly']);
                    if ($oneTimeKeys !== array_keys(array_values($oneTimeKeys)) || $monthlyKeys !== array_keys(array_values($monthlyKeys))) {
                        $needsNormalization = true;
                    }
                }
            } else {
                $flatAmounts = array_filter(array_values($state), fn ($v) => is_numeric($v) && $v > 0);
                if (! empty($flatAmounts)) {
                    $needsNormalization = true;
                }
            }

            if ($needsNormalization) {
                $oneTime = isset($state['one_time']) && is_array($state['one_time'])
                    ? array_values($state['one_time'])
                    : (isset($flatAmounts) ? array_map(fn ($v) => ['amount' => (string) $v, 'label' => ''], $flatAmounts) : []);

                $monthly = isset($state['monthly']) && is_array($state['monthly'])
                    ? array_values($state['monthly'])
                    : (isset($flatAmounts) ? array_map(fn ($v) => ['amount' => (string) $v, 'label' => ''], $flatAmounts) : []);

                if (empty($oneTime) && empty($monthly)) {
                    $oneTime = [
                        ['amount' => '30', 'label' => ''],
                        ['amount' => '50', 'label' => ''],
                        ['amount' => '100', 'label' => ''],
                        ['amount' => '200', 'label' => ''],
                        ['amount' => '500', 'label' => ''],
                        ['amount' => '1000', 'label' => ''],
                    ];
                    $monthly = [
                        ['amount' => '200', 'label' => ''],
                        ['amount' => '100', 'label' => ''],
                        ['amount' => '50', 'label' => ''],
                        ['amount' => '30', 'label' => ''],
                        ['amount' => '10', 'label' => ''],
                        ['amount' => '5', 'label' => ''],
                    ];
                }

                $component->state([
                    'one_time' => $oneTime,
                    'monthly' => $monthly,
                    'default_monthly' => $state['default_monthly'] ?? '25',
                    'impact_enabled' => $state['impact_enabled'] ?? false,
                ]);
            }
        });
    }
}
