<?php

use App\Enums\TrackingProvider;

it('renders all credential fields as plain text inputs, not masked password fields', function () {
    foreach (TrackingProvider::cases() as $provider) {
        foreach ($provider->credentialFields() as $field) {
            expect($field['type'])->toBe('text', "{$provider->value}.{$field['key']} should render as a plain text field");
        }
    }
});
