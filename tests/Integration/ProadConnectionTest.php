<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Planyt\Organisation\Integration\Proad\ProadConnection;
use Planyt\Organisation\Security\EncryptedTokenStore;

final class ProadConnectionTest extends TestCase
{
    public function testStoresApiKeyEncryptedPerUser(): void
    {
        $root = sys_get_temp_dir() . '/planyt-proad-' . bin2hex(random_bytes(6));
        $store = new EncryptedTokenStore($root, str_repeat('k', 32));
        $connection = new ProadConnection($store, 'https://proad.example.com');

        $connection->connect('user@example.com', 'secret-api-key');

        self::assertTrue($connection->connected('user@example.com'));
        self::assertSame('secret-api-key', $connection->apiKey('user@example.com'));

        $raw = file_get_contents($root . '/users/user_example.com/tokens.json');

        self::assertIsString($raw);
        self::assertStringNotContainsString('secret-api-key', $raw);
    }
}
