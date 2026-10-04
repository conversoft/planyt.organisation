<?php

declare(strict_types=1);

namespace Planyt\Organisation\Tests\Security;

use PHPUnit\Framework\TestCase;
use Planyt\Organisation\Security\EncryptedTokenStore;

final class EncryptedTokenStoreTest extends TestCase
{
    public function testStoresSecretsEncryptedAtRestInSharedJsonFile(): void
    {
        $root = sys_get_temp_dir() . '/planyt-token-test-' . bin2hex(random_bytes(6));
        $store = new EncryptedTokenStore($root, 'a-very-long-test-application-key');

        $store->save('user', 'google', 'person@example.com', [
            'account_id' => 'person@example.com',
            'access_token' => 'secret-access-token',
        ]);
        $store->save('user', 'trello', 'member-1', [
            'account_id' => 'member-1',
            'access_token' => 'trello-secret-token',
        ]);

        $path = $root . '/users/user/tokens.json';
        $raw = (string) file_get_contents($path);

        self::assertStringNotContainsString('secret-access-token', $raw);
        self::assertStringNotContainsString('trello-secret-token', $raw);
        self::assertSame(
            'secret-access-token',
            $store->load('user', 'google', 'person@example.com')['access_token'],
        );
        self::assertSame(
            'trello-secret-token',
            $store->load('user', 'trello', 'member-1')['access_token'],
        );
    }
}
