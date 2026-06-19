<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $elements = DB::table('elements')->whereNotNull('config')->get();

        foreach ($elements as $element) {
            $config = json_decode($element->config, true);
            if (! is_array($config)) {
                continue;
            }

            $buttonSize = $config['button_size'] ?? null;
            if (! is_string($buttonSize)) {
                continue;
            }

            // Convert old class-based values to named sizes
            $normalized = match (true) {
                str_contains($buttonSize, 'px-4') => 'small',
                str_contains($buttonSize, 'px-8') => 'large',
                default => 'medium',
            };

            $config['button_size'] = $normalized;
            DB::table('elements')->where('id', $element->id)->update(['config' => $config]);
        }
    }

    public function down(): void
    {
        // No reverse — old class format is discarded
    }
};
