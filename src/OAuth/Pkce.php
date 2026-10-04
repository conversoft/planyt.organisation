<?php

declare(strict_types=1);

namespace Planyt\Organisation\OAuth;

final class Pkce
{
    /** @return array{verifier: string, challenge: string} */
    public static function create(): array
    {
        $verifier = self::base64Url(random_bytes(64));
        $challenge = self::base64Url(hash('sha256', $verifier, true));

        return ['verifier' => $verifier, 'challenge' => $challenge];
    }

    private static function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
