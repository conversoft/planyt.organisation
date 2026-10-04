<?php

declare(strict_types=1);

namespace Planyt\Organisation\Integration\Google;

use Planyt\Organisation\Http\HttpClientInterface;

final class GoogleOAuthClient
{
    public const SCOPES = [
        'https://www.googleapis.com/auth/gmail.readonly',
        'https://www.googleapis.com/auth/calendar.events',
        'https://www.googleapis.com/auth/drive.readonly',
    ];

    public function __construct(
        private readonly HttpClientInterface $http,
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly string $redirectUri,
    ) {
    }

    public function authorizationUrl(string $state): string
    {
        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => implode(' ', self::SCOPES),
            'access_type' => 'offline',
            'include_granted_scopes' => 'true',
            'prompt' => 'consent',
            'state' => $state,
        ]);
    }

    /** @return array<string, mixed> */
    public function exchangeCode(string $code): array
    {
        return $this->http->postForm('https://oauth2.googleapis.com/token', [], [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->redirectUri,
        ]);
    }

    /** @return array<string, mixed> */
    public function refresh(string $refreshToken): array
    {
        return $this->http->postForm('https://oauth2.googleapis.com/token', [], [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
        ]);
    }
}
