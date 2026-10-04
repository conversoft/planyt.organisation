<?php

declare(strict_types=1);

namespace Planyt\Organisation\Workflow;

use RuntimeException;

final class UserActionSession
{
    public function __construct()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    public function issue(string $purpose, string $subjectId): string
    {
        $token = bin2hex(random_bytes(24));
        $_SESSION['user_actions'][$token] = [
            'purpose' => $purpose,
            'subject_id' => $subjectId,
            'issued_at' => time(),
        ];

        return $token;
    }

    /** @return array{purpose: string, subject_id: string, issued_at: int} */
    public function consume(string $token, string $purpose, string $subjectId): array
    {
        $payload = $_SESSION['user_actions'][$token] ?? null;
        unset($_SESSION['user_actions'][$token]);

        if (!is_array($payload)
            || ($payload['purpose'] ?? null) !== $purpose
            || ($payload['subject_id'] ?? null) !== $subjectId
            || (int) ($payload['issued_at'] ?? 0) < time() - 1800
        ) {
            throw new RuntimeException('Invalid or expired user action.');
        }

        return [
            'purpose' => (string) $payload['purpose'],
            'subject_id' => (string) $payload['subject_id'],
            'issued_at' => (int) $payload['issued_at'],
        ];
    }
}
