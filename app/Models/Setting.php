<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

/**
 * @property int $id
 * @property string $key
 * @property string|null $value
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereValue($value)
 *
 * @mixin \Eloquent
 */
class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    /**
     * Keys whose values should be stored encrypted at rest.
     *
     * @var list<string>
     */
    protected static array $encryptedKeys = [
        'mail_password',
        'ses_secret',
        'ses_webhook_token',
        'mailgun_secret',
        'postmark_token',
    ];

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = static::where('key', $key)->value('value');

        if ($value === null) {
            return $default;
        }

        return self::shouldEncrypt($key)
            ? self::decryptValue($value, $default)
            : $value;
    }

    public static function set(string $key, mixed $value): void
    {
        if ($value !== null && self::shouldEncrypt($key)) {
            $value = Crypt::encryptString((string) $value);
        }

        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    private static function shouldEncrypt(string $key): bool
    {
        return in_array($key, self::$encryptedKeys, true);
    }

    private static function decryptValue(string $value, mixed $default): mixed
    {
        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            return $value;
        }
    }
}
