<?php

declare(strict_types=1);

namespace Planyt\Organisation\Config;

use RuntimeException;

final class Env
{
    public static function get(string $name, ?string $default = null): ?string
    {
        $value = getenv($name);

        if ($value === false || $value === '') {
            return $default;
        }

        return $value;
    }

    public static function require(string $name): string
    {
        $value = self::get($name);

        if ($value === null) {
            throw new RuntimeException(sprintf('Missing required environment variable: %s', $name));
        }

        return $value;
    }
}
