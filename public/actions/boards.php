<?php

declare(strict_types=1);

use Planyt\Organisation\Auth\CurrentUser;
use Planyt\Organisation\Storage\UserPreferencesRepository;

$root = dirname(__DIR__, 2);
require $root . '/vendor/autoload.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new RuntimeException('Board selection requires POST.');
    }

    $accountId = (string) ($_POST['account_id'] ?? '');
    $boards = $_POST['boards'] ?? [];

    if ($accountId === '' || !is_array($boards)) {
        throw new RuntimeException('Invalid Trello board selection.');
    }

    $boards = array_values(array_unique(array_filter(
        array_map('strval', $boards),
        static fn (string $id): bool => $id !== '',
    )));

    $userId = CurrentUser::id();
    $repository = new UserPreferencesRepository($root . '/storage');
    $preferences = $repository->load($userId);
    $preferences['trello_boards'] ??= [];
    $preferences['trello_boards'][$accountId] = $boards;
    $repository->save($userId, $preferences);

    header('Location: /?boards_saved=1', true, 302);
} catch (Throwable $exception) {
    header('Location: /?integration_error=' . rawurlencode($exception->getMessage()), true, 302);
}

exit;
