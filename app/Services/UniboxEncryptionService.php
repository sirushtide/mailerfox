<?php

namespace App\Services;

class UniboxEncryptionService
{
    private static ?string $cachedKey = null;

    public static function encrypt(string $plaintext): string
    {
        $key = self::getKey();
        $iv = random_bytes(12);
        $tag = '';

        $ciphertext = openssl_encrypt(
            $plaintext,
            'aes-256-gcm',
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            16
        );

        if ($ciphertext === false) {
            throw new \RuntimeException('UniBox encryption failed');
        }

        return base64_encode($iv . $tag . $ciphertext);
    }

    public static function decrypt(string $payload): ?string
    {
        try {
            $data = base64_decode($payload, true);
            if ($data === false || strlen($data) < 28) {
                return null;
            }

            $iv = substr($data, 0, 12);
            $tag = substr($data, 12, 16);
            $ciphertext = substr($data, 28);

            $key = self::getKey();

            $plaintext = openssl_decrypt(
                $ciphertext,
                'aes-256-gcm',
                $key,
                OPENSSL_RAW_DATA,
                $iv,
                $tag
            );

            return $plaintext !== false ? $plaintext : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function generateKey(): string
    {
        return 'base64:' . base64_encode(random_bytes(32));
    }

    public static function getKey(): string
    {
        if (self::$cachedKey !== null) {
            return self::$cachedKey;
        }

        $cfgKey = config('mailpurse.unibox.enc_key');

        if (!empty($cfgKey)) {
            if (str_starts_with($cfgKey, 'base64:')) {
                $decoded = base64_decode(substr($cfgKey, 7), true);
                if ($decoded !== false && strlen($decoded) === 32) {
                    self::$cachedKey = $decoded;
                    return self::$cachedKey;
                }
            }
            self::$cachedKey = hash('sha256', $cfgKey, true);
            return self::$cachedKey;
        }

        self::$cachedKey = hash('sha256', 'mailpurse-unibox-enc-v1', true);
        \Illuminate\Support\Facades\Log::warning('UniBox encryption key is not configured; credentials are encrypted with a publicly-known default key. Set mailpurse.unibox.enc_key (e.g. UNIBOX_ENC_KEY).');

        return self::$cachedKey;
    }
}
