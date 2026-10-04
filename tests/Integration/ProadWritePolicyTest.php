<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Planyt\Organisation\Integration\Proad\ProadCapability;
use Planyt\Organisation\Integration\Proad\ProadWritePolicy;
use RuntimeException;

final class ProadWritePolicyTest extends TestCase
{
    public function testWriteRequiresExplicitUserAction(): void
    {
        $this->expectException(RuntimeException::class);

        (new ProadWritePolicy())->assertAllowed(
            [ProadCapability::BOOK_TIME],
            ProadCapability::BOOK_TIME,
            false,
        );
    }

    public function testWriteRequiresMatchingCapability(): void
    {
        $this->expectException(RuntimeException::class);

        (new ProadWritePolicy())->assertAllowed(
            [ProadCapability::READ_PROJECTS],
            ProadCapability::CREATE_PROJECT,
            true,
        );
    }

    public function testMatchingCapabilityAndExplicitActionAreAllowed(): void
    {
        (new ProadWritePolicy())->assertAllowed(
            [ProadCapability::BOOK_TIME],
            ProadCapability::BOOK_TIME,
            true,
        );

        self::assertTrue(true);
    }
}
