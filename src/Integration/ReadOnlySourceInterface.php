<?php

declare(strict_types=1);

namespace Planyt\Organisation\Integration;

interface ReadOnlySourceInterface
{
    /** @return array<int, array<string, mixed>> */
    public function pull(string $userId): array;
}
