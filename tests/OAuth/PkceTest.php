<?php

declare(strict_types=1);

namespace Planyt\Organisation\Tests\OAuth;

use PHPUnit\Framework\TestCase;
use Planyt\Organisation\OAuth\Pkce;

final class PkceTest extends TestCase
{
    public function testCreatesVerifierAndS256Challenge(): void
    {
        $pair = Pkce::create();

        self::assertNotSame('', $pair['verifier']);
        self::assertNotSame('', $pair['challenge']);
        self::assertDoesNotMatchRegularExpression('/[+=\/]/', $pair['challenge']);
    }
}
