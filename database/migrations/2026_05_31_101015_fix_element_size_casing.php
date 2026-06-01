<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private const SIZE_MAP = [
        'small' => 'Small',
        'medium' => 'Medium',
        'large' => 'Large',
    ];

    public function up(): void
    {
        DB::table('elements')
            ->where(function ($query) {
                foreach (self::SIZE_MAP as $old => $new) {
                    $query->orWhere('config', 'like', '%"size":"'.$old.'"%');
                }
            })
            ->get()
            ->each(function (object $element): void {
                $config = is_string($element->config)
                    ? json_decode($element->config, true)
                    : (array) $element->config;

                if (isset($config['size'], self::SIZE_MAP[$config['size']])) {
                    $config['size'] = self::SIZE_MAP[$config['size']];
                    DB::table('elements')
                        ->where('id', $element->id)
                        ->update(['config' => json_encode($config)]);
                }
            });
    }

    public function down(): void
    {
        $reverse = array_flip(self::SIZE_MAP);

        DB::table('elements')
            ->where(function ($query) use ($reverse) {
                foreach ($reverse as $new => $old) {
                    $query->orWhere('config', 'like', '%"size":"'.$new.'"%');
                }
            })
            ->get()
            ->each(function (object $element) use ($reverse): void {
                $config = is_string($element->config)
                    ? json_decode($element->config, true)
                    : (array) $element->config;

                if (isset($config['size'], $reverse[$config['size']])) {
                    $config['size'] = $reverse[$config['size']];
                    DB::table('elements')
                        ->where('id', $element->id)
                        ->update(['config' => json_encode($config)]);
                }
            });
    }
};
