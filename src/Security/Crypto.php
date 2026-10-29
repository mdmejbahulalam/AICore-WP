<?php

declare(strict_types=1);

namespace AICore\WP\Security;

/**
 * Encrypts secrets (provider API keys) at rest with libsodium, keyed from the site's WordPress salts.
 * Values written by older versions of the plugin were plain text; decrypt() passes those through unchanged.
 * If the salts change, stored ciphertext can no longer be opened and reads back as an empty string.
 */
final class Crypto
{
    private const PREFIX = 'enc:v1:';

    public function encrypt(string $plain): string
    {
        if ($plain === '') {
            return '';
        }
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        return self::PREFIX . base64_encode($nonce . sodium_crypto_secretbox($plain, $nonce, $this->key()));
    }

    public function decrypt(string $stored): string
    {
        if (! $this->isEncrypted($stored)) {
            return $stored;
        }
        $raw = base64_decode(substr($stored, strlen(self::PREFIX)), true);
        if ($raw === false || strlen($raw) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
            return '';
        }
        $nonce = substr($raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $plain = sodium_crypto_secretbox_open(substr($raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES), $nonce, $this->key());
        return $plain === false ? '' : $plain;
    }

    public function isEncrypted(string $stored): bool
    {
        return str_starts_with($stored, self::PREFIX);
    }

    private function key(): string
    {
        return sodium_crypto_generichash(wp_salt('auth') . '|aicore-wp|secrets', '', SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
    }
}
