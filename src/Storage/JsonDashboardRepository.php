<?php

declare(strict_types=1);

namespace Planyt\Organisation\Storage;

use RuntimeException;

final class JsonDashboardRepository
{
    /** @return array<string, mixed> */
    public function load(string $path): array
    {
        if (!is_file($path)) {
            throw new RuntimeException(sprintf('Dashboard file not found: %s', $path));
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new RuntimeException(sprintf('Dashboard file cannot be read: %s', $path));
        }

        $data = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($data)) {
            throw new RuntimeException('Dashboard JSON must contain an object.');
        }

        return $data;
    }
}
