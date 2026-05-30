<?php

use App\Models\Element;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $map = [
            'small' => 'Small',
            'medium' => 'Medium',
            'large' => 'Large',
            'extra large' => 'Extra Large',
        ];

        foreach (Element::cursor() as $element) {
            $config = $element->config ?? [];
            $changed = false;

            if (isset($config['size']) && is_string($config['size'])) {
                $lower = strtolower($config['size']);
                if (array_key_exists($lower, $map) && $config['size'] !== $map[$lower]) {
                    $config['size'] = $map[$lower];
                    $changed = true;
                }
            }

            if ($changed) {
                $element->config = $config;
                $element->saveQuietly();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Not reversible without data loss (existing Proper Case values become ambiguous)
    }
};
