<?php

declare(strict_types=1);

namespace Planyt\Organisation\Integration\Proad;

use RuntimeException;

final class ProadWritePolicy
{
    /** @param array<int, string> $capabilities */
    public function assertAllowed(array $capabilities, string $requiredCapability, bool $explicitUserAction): void
    {
        if (!$explicitUserAction) {
            throw new RuntimeException('PROAD-Schreibaktionen benötigen eine ausdrückliche Nutzeraktion.');
        }

        if (!in_array($requiredCapability, $capabilities, true)) {
            throw new RuntimeException('Für diese PROAD-Aktion fehlt die erforderliche Berechtigung.');
        }
    }
}
