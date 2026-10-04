<?php

declare(strict_types=1);

namespace Planyt\Organisation\Security;

use RuntimeException;

final class EncryptedTokenStore
{
    public function __construct(
        private readonly string $storageRoot,
        private readonly string $applicationKey,
    ) {
        if (!function_exists('sodium_crypto_secretbox')) {
            throw new RuntimeException('The sodium PHP extension is required.');
        }

        if (strlen($this->applicationKey) < 20) {
            throw new RuntimeException('APP_KEY must contain at least 20 characters.');
        }
    }

    /** @param array<string, mixed> $data */
    public function save(string $userId, string $provider, string $accountId, array $data): void
    {
        $directory = $this->directory($userId, $provider);

        if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new RuntimeException('Unable to create token storage directory.');
        }

        $payload = json_encode($data, JSON_THROW_ON_ERROR);
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $cipher = sodium_crypto_secretbox($payload, $nonce, $this->key());
        $encoded = base64_encode($nonce . $cipher);
        $path = $directory . '/' . $this->safe($accountId) . '.token';

        if (file_put_contents($path, $encoded, LOCK_EX) === false) {
            throw new RuntimeException('Unable to store encrypted connection data.');
        }

        chmod($path, 0600);
    }

    /** @return array<string, mixed>|null */
    public function load(string $userId, string $provider, string $accountId): ?array
    {
        $path = $this->directory($userId, $provider) . '/' . $this->safe($accountId) . '.token';

        if (!is_file($path)) {
            return null;
        }

        $encoded = file_get_contents($path);

        if ($encoded === false) {
            throw new RuntimeException('Unable to read encrypted connection data.');
        }

        $raw = base64_decode($encoded, true);

        if ($raw === false || strlen($raw) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
            throw new RuntimeException('Invalid encrypted connection data.');
        }

        $nonce = substr($raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $cipher = substr($raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $payload = sodium_crypto_secretbox_open($cipher, $nonce, $this->key());

        if ($payload === false) {
            throw new RuntimeException('Unable to decrypt connection data.');
        }

        $data = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);

        return is_array($data) ? $data : null;
    }

    /** @return array<int, array<string, mixed>> */
    public function list(string $userId, string $provider): array
    {
        $directory = $this->directory($userId, $provider);

        if (!is_dir($directory)) {
            return [];
        }

        $items = [];

        foreach (glob($directory . '/*.token') ?: [] as $path) {
            $accountId = basename($path, '.token');
            $data = $this->load($userId, $provider, $accountId);

            if ($data !== null) {
                $items[] = $data;
            }
        }

        return $items;
    }

    private function key(): string
    {
        return sodium_crypto_generichash($this->applicationKey, '', SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
    }

    private function directory(string $userId, string $provider): string
    {
        return rtrim($this->storageRoot, '/') . '/users/' . $this->safe($userId) . '/connections/' . $this->safe($provider);
    }

    private function safe(string $value): string
    {
        $safe = preg_replace('/[^a-zA-Z0-9._-]/', '_', $value);

        return $safe === null || $safe === '' ? 'default' : $safe;
    }
}
