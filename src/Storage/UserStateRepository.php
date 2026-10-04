<?php

declare(strict_types=1);

namespace Planyt\Organisation\Storage;

use RuntimeException;

final class UserStateRepository
{
    public function __construct(private readonly string $storageRoot)
    {
    }

    /** @return array<string, mixed> */
    public function load(string $userId): array
    {
        $path = $this->path($userId);

        if (!is_file($path)) {
            return [
                'user' => ['id' => $userId],
                'updated_at' => null,
                'today' => [],
                'unscheduled' => [],
                'emails' => [],
                'trello' => [],
            ];
        }

        $raw = file_get_contents($path);

        if ($raw === false) {
            throw new RuntimeException('Unable to read user state.');
        }

        $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);

        return is_array($data) ? $data : [];
    }

    /** @param array<string, mixed> $state */
    public function save(string $userId, array $state): void
    {
        $path = $this->path($userId);
        $directory = dirname($path);

        if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new RuntimeException('Unable to create user state directory.');
        }

        $json = json_encode(
            $state,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        );

        if (file_put_contents($path, $json . PHP_EOL, LOCK_EX) === false) {
            throw new RuntimeException('Unable to write user state.');
        }

        chmod($path, 0600);
    }

    private function path(string $userId): string
    {
        $safe = preg_replace('/[^a-zA-Z0-9._-]/', '_', $userId) ?: 'default';

        return rtrim($this->storageRoot, '/') . '/users/' . $safe . '/dashboard.json';
    }
}
