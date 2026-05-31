<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $elements = DB::table('elements')
            ->where('type', 'qr_code')
            ->get(['id', 'config']);

        foreach ($elements as $element) {
            $config = json_decode($element->config, true);

            if (isset($config['size']) && is_int($config['size'])) {
                $config['size'] = match ($config['size']) {
                    150 => 'Small',
                    200 => 'Medium',
                    250 => 'Large',
                    300 => 'Extra Large',
                    default => 'Medium',
                };

                DB::table('elements')
                    ->where('id', $element->id)
                    ->update(['config' => json_encode($config)]);
            }
        }
    }

    public function down(): void
    {
        $elements = DB::table('elements')
            ->where('type', 'qr_code')
            ->get(['id', 'config']);

        foreach ($elements as $element) {
            $config = json_decode($element->config, true);

            if (isset($config['size']) && is_string($config['size'])) {
                $config['size'] = match ($config['size']) {
                    'Small' => 150,
                    'Medium' => 200,
                    'Large' => 250,
                    'Extra Large' => 300,
                    default => 200,
                };

                DB::table('elements')
                    ->where('id', $element->id)
                    ->update(['config' => json_encode($config)]);
            }
        }
    }
};
