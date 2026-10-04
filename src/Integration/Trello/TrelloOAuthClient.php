<?php

declare(strict_types=1);

namespace Planyt\Organisation\Integration\Trello;

use Planyt\Organisation\Http\HttpClientInterface;

final class TrelloOAuthClient
{
    public const SCOPES = [
        'read:member:trello',
        'read:board:trello',
        'offline_access',
    ];

    public function __construct(
        private readonly HttpClientInterface $http,
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly string $redirectUri,
    ) {
    }

    public function authorizationUrl(string $state, string $codeChallenge): string
    {
        return 'https://auth.atlassian.com/authorize?' . http_build_query([
            'client_id' => $this->clientId,
            'scope' => implode(' ', self::SCOPES),
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'prompt' => 'consent',
            'state' => $state,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
        ]);
    }

    /** @return array<string, mixed> */
    public function exchangeCode(string $code, string $codeVerifier): array
    {
        return $this->http->postJson('https://auth.atlassian.com/oauth/token', [], [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->redirectUri,
            'code' => $code,
            'code_verifier' => $codeVerifier,
        ]);
    }

    /** @return array<string, mixed> */
    public function refresh(string $refreshToken): array
    {
        return $this->http->postJson('https://auth.atlassian.com/oauth/token', [], [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
        ]);
    }
}
