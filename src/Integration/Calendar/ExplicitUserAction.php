<?php

declare(strict_types=1);

namespace Planyt\Organisation\Integration\Calendar;

use InvalidArgumentException;

final class ExplicitUserAction
{
    public function __construct(
        public readonly string $actionId,
        public readonly int $confirmedAt,
    ) {
        if ($this->actionId === '') {
            throw new InvalidArgumentException('Action ID is required.');
        }

        if ($this->confirmedAt < time() - 300 || $this->confirmedAt > time() + 30) {
            throw new InvalidArgumentException('Calendar confirmation is expired or invalid.');
        }
    }
}
