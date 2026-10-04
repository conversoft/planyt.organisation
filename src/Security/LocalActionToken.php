<?php

declare(strict_types=1);

namespace Planyt\Organisation\Security;

use RuntimeException;

final class LocalActionToken
{
    public function __construct(private readonly string $key)
    {
    }

    public function issue(string $purpose, string $userId, string $subjectId, string $value): string
    {
        return hash_hmac('sha256', $this->payload($purpose, $userId, $subjectId, $value), $this->key);
    }

    public function verify(
        string $token,
        string $purpose,
        string $userId,
        string $subjectId,
        string $value,
    ): void {
        $expected = $this->issue($purpose, $userId, $subjectId, $value);

        if ($token === '' || !hash_equals($expected, $token)) {
            throw new RuntimeException('Ungültige Planyt-Aktion. Bitte die Seite neu laden und erneut versuchen.');
        }
    }

    private function payload(string $purpose, string $userId, string $subjectId, string $value): string
    {
        return implode('|', [$purpose, $userId, $subjectId, $value]);
    }
}
