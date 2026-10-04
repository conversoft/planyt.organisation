<?php

declare(strict_types=1);

namespace Planyt\Organisation\Integration\Proad;

use Planyt\Organisation\Security\EncryptedTokenStore;
use RuntimeException;

final class ProadConnection
{
    public function __construct(
        private readonly EncryptedTokenStore $tokens,
        private readonly string $baseUrl,
    ) {
    }

    public function configured(): bool
    {
        return trim($this->baseUrl) !== '';
    }

    public function connected(string $userId): bool
    {
        return $this->tokens->load($userId, 'proad', 'default') !== null;
    }

    public function connect(string $userId, string $apiKey): void
    {
        $apiKey = trim($apiKey);

        if ($apiKey === '') {
            throw new RuntimeException('Bitte einen PROAD API-Key eingeben.');
        }

        $this->tokens->save($userId, 'proad', 'default', [
            'account_id' => 'default',
            'api_key' => $apiKey,
            'base_url' => $this->baseUrl,
        ]);
    }

    public function apiKey(string $userId): string
    {
        $connection = $this->tokens->load($userId, 'proad', 'default');

        if (!is_array($connection)) {
            throw new RuntimeException('PROAD ist noch nicht verbunden.');
        }

        $apiKey = trim((string) ($connection['api_key'] ?? ''));

        if ($apiKey === '') {
            throw new RuntimeException('PROAD API-Key fehlt.');
        }

        return $apiKey;
    }

    public function baseUrl(): string
    {
        return $this->baseUrl;
    }
}
