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
        $document = $this->readDocument($userId);
        $key = $this->entryKey($provider, $accountId);

        $document['tokens'][$key] = [
            'provider' => $provider,
            'account_id' => $accountId,
            'encrypted' => $this->encrypt($data),
            'updated_at' => gmdate(DATE_ATOM),
        ];

        $this->writeDocument($userId, $document);
    }

    /** @return array<string, mixed>|null */
    public function load(string $userId, string $provider, string $accountId): ?array
    {
        $document = $this->readDocument($userId);
        $entry = $document['tokens'][$this->entryKey($provider, $accountId)] ?? null;

        if (is_array($entry) && isset($entry['encrypted'])) {
            return $this->decrypt((string) $entry['encrypted']);
        }

        return $this->loadLegacy($userId, $provider, $accountId);
    }

    /** @return array<int, array<string, mixed>> */
    public function list(string $userId, string $provider): array
    {
        $document = $this->readDocument($userId);
        $items = [];

        foreach ($document['tokens'] ?? [] as $entry) {
            if (!is_array($entry) || ($entry['provider'] ?? null) !== $provider) {
                continue;
            }

            $data = $this->decrypt((string) ($entry['encrypted'] ?? ''));

            if ($data !== null) {
                $items[] = $data;
            }
        }

        foreach ($this->legacyFiles($userId, $provider) as $path) {
            $accountId = basename($path, '.token');

            if ($this->containsAccount($items, $accountId)) {
                continue;
            }

            $legacy = $this->loadLegacy($userId, $provider, $accountId);

            if ($legacy !== null) {
                $this->save($userId, $provider, $accountId, $legacy);
                $items[] = $legacy;
            }
        }

        return $items;
    }

    /** @return array{version:int,tokens:array<string, array<string, mixed>>} */
    private function readDocument(string $userId): array
    {
        $path = $this->path($userId);

        if (!is_file($path)) {
            return ['version' => 1, 'tokens' => []];
        }

        $raw = file_get_contents($path);

        if ($raw === false) {
            throw new RuntimeException('Unable to read tokens.json.');
        }

        $document = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($document)) {
            throw new RuntimeException('tokens.json must contain a JSON object.');
        }

        $document['version'] ??= 1;
        $document['tokens'] ??= [];

        return $document;
    }

    /** @param array<string, mixed> $document */
    private function writeDocument(string $userId, array $document): void
    {
        $path = $this->path($userId);
        $directory = dirname($path);

        if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new RuntimeException('Unable to create user token storage directory.');
        }

        $json = json_encode(
            $document,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        );

        if (file_put_contents($path, $json . PHP_EOL, LOCK_EX) === false) {
            throw new RuntimeException('Unable to write tokens.json.');
        }

        chmod($path, 0600);
    }

    /** @param array<string, mixed> $data */
    private function encrypt(array $data): string
    {
        $payload = json_encode($data, JSON_THROW_ON_ERROR);
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $cipher = sodium_crypto_secretbox($payload, $nonce, $this->key());

        return base64_encode($nonce . $cipher);
    }

    /** @return array<string, mixed>|null */
    private function decrypt(string $encoded): ?array
    {
        $raw = base64_decode($encoded, true);

        if ($raw === false || strlen($raw) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
            return null;
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

    /** @return array<string, mixed>|null */
    private function loadLegacy(string $userId, string $provider, string $accountId): ?array
    {
        $path = $this->legacyDirectory($userId, $provider) . '/' . $this->safe($accountId) . '.token';

        if (!is_file($path)) {
            return null;
        }

        $encoded = file_get_contents($path);

        if ($encoded === false) {
            throw new RuntimeException('Unable to read legacy token file.');
        }

        return $this->decrypt($encoded);
    }

    /** @return array<int, string> */
    private function legacyFiles(string $userId, string $provider): array
    {
        $directory = $this->legacyDirectory($userId, $provider);

        if (!is_dir($directory)) {
            return [];
        }

        return glob($directory . '/*.token') ?: [];
    }

    /** @param array<int, array<string, mixed>> $items */
    private function containsAccount(array $items, string $accountId): bool
    {
        foreach ($items as $item) {
            if ((string) ($item['account_id'] ?? '') === $accountId) {
                return true;
            }
        }

        return false;
    }

    private function entryKey(string $provider, string $accountId): string
    {
        return $this->safe($provider) . ':' . $this->safe($accountId);
    }

    private function path(string $userId): string
    {
        return rtrim($this->storageRoot, '/') . '/users/' . $this->safe($userId) . '/tokens.json';
    }

    private function legacyDirectory(string $userId, string $provider): string
    {
        return rtrim($this->storageRoot, '/')
            . '/users/' . $this->safe($userId)
            . '/connections/' . $this->safe($provider);
    }

    private function key(): string
    {
        return sodium_crypto_generichash(
            $this->applicationKey,
            '',
            SODIUM_CRYPTO_SECRETBOX_KEYBYTES,
        );
    }

    private function safe(string $value): string
    {
        $safe = preg_replace('/[^a-zA-Z0-9._-]/', '_', $value);

        return $safe === null || $safe === '' ? 'default' : $safe;
    }
}
