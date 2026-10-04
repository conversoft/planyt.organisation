<?php

declare(strict_types=1);

namespace Planyt\Organisation\Auth;

use Planyt\Organisation\Integration\Google\GoogleConnection;

final class ExistingGoogleLogin
{
    public function __construct(private readonly GoogleConnection $connection)
    {
    }

    public function importFromSession(string $userId): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $token = $_SESSION['google_oauth_token']
            ?? $_SESSION['google']['oauth_token']
            ?? $_SESSION['oauth']['google']
            ?? null;

        if (!is_array($token) || !isset($token['access_token'])) {
            return;
        }

        $this->connection->storeFromExistingLogin($userId, $token);
    }
}
