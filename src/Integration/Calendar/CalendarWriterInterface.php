<?php

declare(strict_types=1);

namespace Planyt\Organisation\Integration\Calendar;

interface CalendarWriterInterface
{
    /** @param array<string, mixed> $event */
    public function createPlanningBlock(string $userId, array $event): string;
}
