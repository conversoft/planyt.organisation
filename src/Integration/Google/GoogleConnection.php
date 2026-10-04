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
        private readonly GoogleOAuthClient $oauth,
        private readonly EncryptedTokenStore $tokens,
    ) {
    }

    /** @param array<string, mixed> $token */
    public function storeNew(string $userId, array $token): string
    {
        $accessToken = (string) ($token['access_token'] ?? '');

        if ($accessToken === '') {
            throw new RuntimeException('Google did not return an access token.');
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
        $token['expires_at'] = time() + (int) ($token['expires_in'] ?? 3600);

        $this->tokens->save($userId, 'google', $email, $token);

        return $email;
    }

    public function accessToken(string $userId, string $accountId): string
    {
        $token = $this->tokens->load($userId, 'google', $accountId);

        if ($token === null) {
            throw new RuntimeException('Google connection not found.');
        }

        if ((int) ($token['expires_at'] ?? 0) > time() + 60) {
            return (string) $token['access_token'];
        }

        $refreshToken = (string) ($token['refresh_token'] ?? '');

        if ($refreshToken === '') {
            throw new RuntimeException('Google refresh token is missing; reconnect the account.');
        }

        $refreshed = $this->oauth->refresh($refreshToken);
        $token = array_merge($token, $refreshed);
        $token['refresh_token'] = $refreshToken;
        $token['expires_at'] = time() + (int) ($refreshed['expires_in'] ?? 3600);

        $this->tokens->save($userId, 'google', $accountId, $token);

        return (string) $token['access_token'];
    }

    /** @return array<int, array<string, mixed>> */
    public function accounts(string $userId): array
    {
        return $this->tokens->list($userId, 'google');
    }
}
