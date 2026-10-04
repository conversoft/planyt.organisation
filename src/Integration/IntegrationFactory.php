<?php

declare(strict_types=1);

namespace Planyt\Organisation\Integration;

use Planyt\Organisation\Config\Env;
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
    private ?GoogleOAuthClient $googleOAuth = null;
    private ?GoogleConnection $googleConnection = null;
    private ?TrelloOAuthClient $trelloOAuth = null;
    private ?TrelloConnection $trelloConnection = null;

    public function __construct(private readonly string $root)
    {
        $this->http = new CurlHttpClient();
        $this->tokens = new EncryptedTokenStore(
            $this->root . '/storage',
            Env::require('APP_KEY'),
        );
    }

    public function googleOAuth(): GoogleOAuthClient
    {
        return $this->googleOAuth ??= new GoogleOAuthClient(
            $this->http,
            Env::require('GOOGLE_CLIENT_ID'),
            Env::require('GOOGLE_CLIENT_SECRET'),
            Env::require('GOOGLE_REDIRECT_URI'),
        );
    }

    public function googleConnection(): GoogleConnection
    {
        return $this->googleConnection ??= new GoogleConnection(
            $this->http,
            $this->googleOAuth(),
            $this->tokens,
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
        return $this->trelloOAuth ??= new TrelloOAuthClient(
            $this->http,
            Env::require('TRELLO_CLIENT_ID'),
            Env::require('TRELLO_CLIENT_SECRET'),
            Env::require('TRELLO_REDIRECT_URI'),
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
