<?php

declare(strict_types=1);

namespace Planyt\Organisation\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Planyt\Organisation\Integration\Google\GooglePermissions;
use Planyt\Organisation\Integration\Trello\TrelloOAuthClient;

final class ScopePolicyTest extends TestCase
{
    public function testExistingGoogleLoginMustRemainReadOnlyForGmailAndDrive(): void
    {
        self::assertContains(
            'https://www.googleapis.com/auth/gmail.readonly',
            GooglePermissions::REQUIRED_SCOPES,
        );
        self::assertContains(
            'https://www.googleapis.com/auth/drive.readonly',
            GooglePermissions::REQUIRED_SCOPES,
        );
        self::assertContains(
            'https://www.googleapis.com/auth/calendar.events.owned',
            GooglePermissions::REQUIRED_SCOPES,
        );
        self::assertNotContains(
            'https://www.googleapis.com/auth/gmail.send',
            GooglePermissions::REQUIRED_SCOPES,
        );
        self::assertNotContains(
            'https://www.googleapis.com/auth/gmail.modify',
            GooglePermissions::REQUIRED_SCOPES,
        );
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
