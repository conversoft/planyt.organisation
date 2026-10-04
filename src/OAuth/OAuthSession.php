<?php

declare(strict_types=1);

namespace Planyt\Organisation\OAuth;

use RuntimeException;

final class OAuthSession
{
    public function __construct()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    /** @param array<string, mixed> $payload */
    public function begin(string $provider, array $payload): string
    {
        $state = bin2hex(random_bytes(24));
        $_SESSION['oauth'][$provider][$state] = $payload;

        return $state;
    }

    /** @return array<string, mixed> */
    public function consume(string $provider, string $state): array
    {
        $payload = $_SESSION['oauth'][$provider][$state] ?? null;
        unset($_SESSION['oauth'][$provider][$state]);

        if (!is_array($payload)) {
            throw new RuntimeException('Invalid or expired OAuth state.');
        }

        return $payload;
    }
}
