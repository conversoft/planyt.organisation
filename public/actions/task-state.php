<?php

declare(strict_types=1);

use Planyt\Organisation\Auth\CurrentUser;
use Planyt\Organisation\Storage\UserPreferencesRepository;
use Planyt\Organisation\Storage\UserStateRepository;
use Planyt\Organisation\Workflow\UserActionSession;

$root = dirname(__DIR__, 2);
require $root . '/vendor/autoload.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new RuntimeException('Task state change requires POST.');
    }

    $sourceId = (string) ($_POST['source_id'] ?? '');
    $state = (string) ($_POST['state'] ?? '');
    $token = (string) ($_POST['action_token'] ?? '');

    if ($sourceId === '' || !in_array($state, ['done', 'irrelevant', 'active'], true)) {
        throw new RuntimeException('Invalid task state.');
    }

    (new UserActionSession())->consume($token, 'task-state', $sourceId);

    $userId = CurrentUser::id();
    $repository = new UserPreferencesRepository($root . '/storage');
    $preferences = $repository->load($userId);
    $preferences['task_states'] ??= [];

    if ($state === 'active') {
        unset($preferences['task_states'][$sourceId]);
    } else {
        $preferences['task_states'][$sourceId] = $state;
    }

    $repository->save($userId, $preferences);

    $stateRepository = new UserStateRepository($root . '/storage');
    $dashboard = $stateRepository->load($userId);
    $dashboard['unscheduled'] = array_values(array_filter(
        $dashboard['unscheduled'] ?? [],
        static fn (array $item): bool => (string) ($item['source_id'] ?? '') !== $sourceId,
    ));
    $stateRepository->save($userId, $dashboard);

    header('Location: /?task_state_saved=1#planung', true, 302);
} catch (Throwable $exception) {
    header('Location: /?integration_error=' . rawurlencode($exception->getMessage()) . '#planung', true, 302);
}

exit;
