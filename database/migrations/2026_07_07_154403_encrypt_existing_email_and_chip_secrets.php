<?php

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $emailKeys = [
            'mail_password',
            'ses_secret',
            'mailgun_secret',
            'postmark_token',
        ];

        foreach ($emailKeys as $key) {
            DB::table('settings')
                ->where('key', $key)
                ->whereNotNull('value')
                ->chunkById(100, function ($rows): void {
                    foreach ($rows as $row) {
                        if ($this->isEncrypted($row->value)) {
                            continue;
                        }

                        DB::table('settings')
                            ->where('id', $row->id)
                            ->update(['value' => Crypt::encryptString($row->value)]);
                    }
                });
        }

        DB::table('organizations')
            ->whereNotNull('chip_api_key')
            ->orWhereNotNull('chip_webhook_public_key')
            ->chunkById(100, function ($rows): void {
                foreach ($rows as $row) {
                    $apiKey = $row->chip_api_key !== null && ! $this->isEncrypted($row->chip_api_key)
                        ? Crypt::encryptString($row->chip_api_key)
                        : $row->chip_api_key;

                    $publicKey = $row->chip_webhook_public_key !== null && ! $this->isEncrypted($row->chip_webhook_public_key)
                        ? Crypt::encryptString($row->chip_webhook_public_key)
                        : $row->chip_webhook_public_key;

                    if ($apiKey === $row->chip_api_key && $publicKey === $row->chip_webhook_public_key) {
                        continue;
                    }

                    DB::table('organizations')
                        ->where('id', $row->id)
                        ->update([
                            'chip_api_key' => $apiKey,
                            'chip_webhook_public_key' => $publicKey,
                        ]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $emailKeys = [
            'mail_password',
            'ses_secret',
            'mailgun_secret',
            'postmark_token',
        ];

        foreach ($emailKeys as $key) {
            DB::table('settings')
                ->where('key', $key)
                ->whereNotNull('value')
                ->chunkById(100, function ($rows): void {
                    foreach ($rows as $row) {
                        $plain = $this->decrypt($row->value);

                        if ($plain === null) {
                            continue;
                        }

                        DB::table('settings')
                            ->where('id', $row->id)
                            ->update(['value' => $plain]);
                    }
                });
        }

        DB::table('organizations')
            ->whereNotNull('chip_api_key')
            ->orWhereNotNull('chip_webhook_public_key')
            ->chunkById(100, function ($rows): void {
                foreach ($rows as $row) {
                    $apiKey = $this->decrypt($row->chip_api_key);
                    $publicKey = $this->decrypt($row->chip_webhook_public_key);

                    if ($apiKey === null && $publicKey === null) {
                        continue;
                    }

                    DB::table('organizations')
                        ->where('id', $row->id)
                        ->update([
                            'chip_api_key' => $apiKey ?? $row->chip_api_key,
                            'chip_webhook_public_key' => $publicKey ?? $row->chip_webhook_public_key,
                        ]);
                }
            });
    }

    private function isEncrypted(?string $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        try {
            Crypt::decryptString($value);

            return true;
        } catch (DecryptException) {
            return false;
        }
    }

    private function decrypt(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            return null;
        }
    }
};
