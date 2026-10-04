<?php

declare(strict_types=1);

namespace Planyt\Organisation\Storage;

use RuntimeException;

final class UserPreferencesRepository
{
    public function __construct(private readonly string $storageRoot)
    {
    }

    /** @return array<string, mixed> */
    public function load(string $userId): array
    {
        $path = $this->path($userId);

        if (!is_file($path)) {
            return ['trello_boards' => [], 'calendar_account' => null, 'task_states' => [], 'email_states' => []];
        }

        $raw = file_get_contents($path);

        if ($raw === false) {
            throw new RuntimeException('Unable to read user preferences.');
        }

        $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);

        return is_array($data) ? $data : [];
    }

    /** @param array<string, mixed> $preferences */
    public function save(string $userId, array $preferences): void
    {
        $path = $this->path($userId);
        $directory = dirname($path);

        if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new RuntimeException('Unable to create preference directory.');
        }

        $json = json_encode(
            $preferences,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        );

        if (file_put_contents($path, $json . PHP_EOL, LOCK_EX) === false) {
            throw new RuntimeException('Unable to write user preferences.');
        }

        chmod($path, 0600);
    }

    private function path(string $userId): string
    {
        $safe = preg_replace('/[^a-zA-Z0-9._-]/', '_', $userId) ?: 'default';

        return rtrim($this->storageRoot, '/') . '/users/' . $safe . '/preferences.json';
    }
}
