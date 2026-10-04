<?php

declare(strict_types=1);

namespace Planyt\Organisation\Integration\Trello;

use Planyt\Organisation\Http\HttpClientInterface;
use Planyt\Organisation\Security\EncryptedTokenStore;
use RuntimeException;

final class TrelloConnection
{
    public function __construct(
        private readonly HttpClientInterface $http,
        private readonly TrelloOAuthClient $oauth,
        private readonly EncryptedTokenStore $tokens,
    ) {
    }

    /** @param array<string, mixed> $token */
    public function storeNew(string $userId, array $token): string
    {
        $accessToken = (string) ($token['access_token'] ?? '');

        if ($accessToken === '') {
            throw new RuntimeException('Trello did not return an access token.');
        }

        $member = $this->http->get(
            'https://api.trello.com/1/members/me',
            ['Authorization' => 'Bearer ' . $accessToken],
            ['fields' => 'id,username,fullName'],
        );
        $memberId = (string) ($member['id'] ?? '');

        if ($memberId === '') {
            throw new RuntimeException('Unable to determine Trello member.');
        }

        $token['provider'] = 'trello';
        $token['account_id'] = $memberId;
        $token['username'] = (string) ($member['username'] ?? '');
        $token['full_name'] = (string) ($member['fullName'] ?? '');
        $token['expires_at'] = time() + (int) ($token['expires_in'] ?? 3600);

        $this->tokens->save($userId, 'trello', $memberId, $token);

        return $memberId;
    }

    public function accessToken(string $userId, string $accountId): string
    {
        $token = $this->tokens->load($userId, 'trello', $accountId);

        if ($token === null) {
            throw new RuntimeException('Trello connection not found.');
        }

        if ((int) ($token['expires_at'] ?? 0) > time() + 60) {
            return (string) $token['access_token'];
        }

        $refreshToken = (string) ($token['refresh_token'] ?? '');

        if ($refreshToken === '') {
            throw new RuntimeException('Trello refresh token is missing; reconnect the account.');
        }

        $refreshed = $this->oauth->refresh($refreshToken);
        $token = array_merge($token, $refreshed);
        $token['expires_at'] = time() + (int) ($refreshed['expires_in'] ?? 3600);

        $this->tokens->save($userId, 'trello', $accountId, $token);

        return (string) $token['access_token'];
    }

    /** @return array<int, array<string, mixed>> */
    public function accounts(string $userId): array
    {
        return $this->tokens->list($userId, 'trello');
    }
}
