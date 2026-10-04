<?php

declare(strict_types=1);

namespace Planyt\Organisation\Integration;

use Planyt\Organisation\Config\InstallationConfig;
use Planyt\Organisation\Http\CurlHttpClient;
use Planyt\Organisation\Integration\Google\CalendarClient;
use Planyt\Organisation\Integration\Google\DriveSource;
use Planyt\Organisation\Integration\Google\GmailSource;
use Planyt\Organisation\Integration\Google\GoogleConnection;
use Planyt\Organisation\Integration\Google\GoogleOAuthClient;
use Planyt\Organisation\Integration\Trello\TrelloConnection;
use Planyt\Organisation\Integration\Trello\TrelloOAuthClient;
use Planyt\Organisation\Integration\Trello\TrelloSource;
use Planyt\Organisation\Security\EncryptedTokenStore;

final class IntegrationFactory
{
    private CurlHttpClient $http;
    private EncryptedTokenStore $tokens;
    private InstallationConfig $installation;
    private ?GoogleOAuthClient $googleOAuth = null;
    private ?GoogleConnection $googleConnection = null;
    private ?TrelloOAuthClient $trelloOAuth = null;
    private ?TrelloConnection $trelloConnection = null;

    public function __construct(private readonly string $root)
    {
        $this->http = new CurlHttpClient();
        $this->installation = new InstallationConfig($this->root . '/storage');
        $this->tokens = new EncryptedTokenStore(
            $this->root . '/storage',
            $this->installation->appKey(),
        );
    }

    public function googleConfigured(): bool
    {
        return $this->installation->google() !== null;
    }

    public function googleAvailable(string $userId): bool
    {
        return $this->googleConnection()->accounts($userId) !== [];
    }

    public function trelloConfigured(): bool
    {
        return $this->installation->trello() !== null;
    }

    public function googleOAuth(): GoogleOAuthClient
    {
        $config = $this->installation->google();

        if ($config === null) {
            throw new \RuntimeException('Google is not enabled for this installation.');
        }

        return $this->googleOAuth ??= new GoogleOAuthClient(
            $this->http,
            $config['client_id'],
            $config['client_secret'],
            $config['redirect_uri'],
        );
    }

    public function googleConnection(): GoogleConnection
    {
        return $this->googleConnection ??= new GoogleConnection(
            $this->http,
            $this->tokens,
            $this->googleConfigured() ? $this->googleOAuth() : null,
        );
    }

    public function gmail(): GmailSource
    {
        return new GmailSource($this->http, $this->googleConnection());
    }

    public function calendar(): CalendarClient
    {
        return new CalendarClient($this->http, $this->googleConnection());
    }

    public function drive(): DriveSource
    {
        return new DriveSource($this->http, $this->googleConnection());
    }

    public function trelloOAuth(): TrelloOAuthClient
    {
        $config = $this->installation->trello();

        if ($config === null) {
            throw new \RuntimeException('Trello is not enabled for this installation.');
        }

        return $this->trelloOAuth ??= new TrelloOAuthClient(
            $this->http,
            $config['client_id'],
            $config['client_secret'],
            $config['redirect_uri'],
        );
    }

    public function trelloConnection(): TrelloConnection
    {
        return $this->trelloConnection ??= new TrelloConnection(
            $this->http,
            $this->trelloOAuth(),
            $this->tokens,
        );
    }

    public function trello(): TrelloSource
    {
        return new TrelloSource($this->http, $this->trelloConnection());
    }

    public function tokenStore(): EncryptedTokenStore
    {
        return $this->tokens;
    }
}
