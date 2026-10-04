<?php

declare(strict_types=1);

namespace Planyt\Organisation\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Planyt\Organisation\Integration\Google\GoogleOAuthClient;
use Planyt\Organisation\Integration\Trello\TrelloOAuthClient;

final class ScopePolicyTest extends TestCase
{
    public function testGoogleNeverRequestsGmailWriteScopes(): void
    {
        self::assertContains('https://www.googleapis.com/auth/gmail.readonly', GoogleOAuthClient::SCOPES);
        self::assertNotContains('https://www.googleapis.com/auth/gmail.send', GoogleOAuthClient::SCOPES);
        self::assertNotContains('https://www.googleapis.com/auth/gmail.modify', GoogleOAuthClient::SCOPES);
    }

    public function testTrelloNeverRequestsWriteScopes(): void
    {
        self::assertContains('read:board:trello', TrelloOAuthClient::SCOPES);
        self::assertContains('read:member:trello', TrelloOAuthClient::SCOPES);

        foreach (TrelloOAuthClient::SCOPES as $scope) {
            self::assertStringNotContainsString('write:', $scope);
        }
    }
}
