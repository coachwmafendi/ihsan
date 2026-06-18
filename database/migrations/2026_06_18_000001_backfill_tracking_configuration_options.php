<?php

use App\Models\TrackingConfiguration;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        foreach (TrackingConfiguration::query()->cursor() as $config) {
            if (empty($config->options)) {
                $config->update([
                    'options' => $config->provider->defaultOptions(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // No-op: we cannot know which rows previously had empty options.
    }
};
