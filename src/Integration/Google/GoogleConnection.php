<?php

declare(strict_types=1);

namespace Planyt\Organisation\Integration\Google;

use RuntimeException;
use Planyt\Organisation\Http\HttpClientInterface;
use Planyt\Organisation\Security\EncryptedTokenStore;

final class GoogleConnection
{
    public function __construct(
        private readonly HttpClientInterface $http,
        private readonly EncryptedTokenStore $tokens,
        private readonly ?GoogleOAuthClient $oauth = null,
    ) {
    }

    /** @param array<string, mixed> $token */
    public function storeNew(string $userId, array $token): string
    {
        return $this->storeFromExistingLogin($userId, $token);
    }

    /** @param array<string, mixed> $token */
    public function storeFromExistingLogin(string $userId, array $token): string
    {
        $accessToken = (string) ($token['access_token'] ?? '');

        if ($accessToken === '') {
            throw new RuntimeException('Existing Google login did not provide an access token.');
        }

        $profile = $this->http->get(
            'https://gmail.googleapis.com/gmail/v1/users/me/profile',
            ['Authorization' => 'Bearer ' . $accessToken],
        );
        $email = strtolower((string) ($profile['emailAddress'] ?? ''));

        if ($email === '') {
            throw new RuntimeException('Unable to determine Google account email address.');
        }

        $token['provider'] = 'google';
        $token['account_id'] = $email;
        $token['email'] = $email;

        if (!isset($token['expires_at']) && isset($token['expires_in'])) {
            $token['expires_at'] = time() + (int) $token['expires_in'];
        }

        $this->tokens->save($userId, 'google', $email, $token);

        return $email;
    }

    public function accessToken(string $userId, string $accountId): string
    {
        $token = $this->tokens->load($userId, 'google', $accountId);

        if ($token === null) {
            throw new RuntimeException('Google login token not found for this user.');
        }

        if ((int) ($token['expires_at'] ?? PHP_INT_MAX) <= time() + 60) {
            $refreshToken = (string) ($token['refresh_token'] ?? '');

            if ($refreshToken === '' || $this->oauth === null) {
                throw new RuntimeException('Google-Anmeldung ist abgelaufen. Bitte erneut mit Google anmelden.');
            }

            $refreshed = $this->oauth->refresh($refreshToken);
            $token = array_merge($token, $refreshed);
            $token['refresh_token'] = $refreshToken;
            $token['expires_at'] = time() + (int) ($refreshed['expires_in'] ?? 3600);
            $this->tokens->save($userId, 'google', $accountId, $token);
        }

        $accessToken = (string) ($token['access_token'] ?? '');

        if ($accessToken === '') {
            throw new RuntimeException('Google access token is missing.');
        }

        return $accessToken;
    }

    /** @return array<int, array<string, mixed>> */
    public function accounts(string $userId): array
    {
        return $this->tokens->list($userId, 'google');
    }
}
