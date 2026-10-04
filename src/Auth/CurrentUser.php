<?php

declare(strict_types=1);

namespace Planyt\Organisation\Auth;

final class CurrentUser
{
    public static function id(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $candidates = [
            $_SESSION['planyt_user_id'] ?? null,
            $_SESSION['user']['email'] ?? null,
            $_SESSION['google']['email'] ?? null,
            $_SERVER['REMOTE_USER'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return strtolower(trim($candidate));
            }
        }

        return 'demo';
    }
}
