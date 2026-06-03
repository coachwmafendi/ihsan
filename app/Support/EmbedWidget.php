<?php

namespace App\Support;

class EmbedWidget
{
    public static function scriptUrl(): string
    {
        return url('/e/widget.js').'?v='.self::version();
    }

    public static function version(): string
    {
        return once(function (): string {
            $path = resource_path('js/widget.js');

            if (is_file($path)) {
                $hash = sha1_file($path);

                if (is_string($hash) && $hash !== '') {
                    return substr($hash, 0, 12);
                }

                $modifiedAt = filemtime($path);

                if ($modifiedAt !== false) {
                    return (string) $modifiedAt;
                }
            }

            return (string) config('app.version', '1');
        });
    }
}
