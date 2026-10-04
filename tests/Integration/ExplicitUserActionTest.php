<?php

declare(strict_types=1);

namespace Planyt\Organisation\Tests\Integration;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Planyt\Organisation\Integration\Calendar\ExplicitUserAction;

final class ExplicitUserActionTest extends TestCase
{
    public function testRejectsExpiredCalendarConfirmation(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ExplicitUserAction('action', time() - 301);
    }

    public function testAcceptsCurrentCalendarConfirmation(): void
    {
        $action = new ExplicitUserAction('action', time());

        self::assertSame('action', $action->actionId);
    }
}
