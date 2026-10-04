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
    ) {
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
            throw new RuntimeException('Google login token expired. Please sign in with Google again.');
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
