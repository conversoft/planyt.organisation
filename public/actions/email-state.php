<?php

declare(strict_types=1);

use Planyt\Organisation\Auth\CurrentUser;
use Planyt\Organisation\Config\InstallationConfig;
use Planyt\Organisation\Security\LocalActionToken;
use Planyt\Organisation\Storage\UserPreferencesRepository;
use Planyt\Organisation\Storage\UserStateRepository;

$root = dirname(__DIR__, 2);
require $root . '/vendor/autoload.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new RuntimeException('Email state change requires POST.');
    }

    $emailId = (string) ($_POST['email_id'] ?? '');
    $state = (string) ($_POST['state'] ?? '');
    $token = (string) ($_POST['action_token'] ?? '');

    if ($emailId === '' || !in_array($state, ['hidden', 'active'], true)) {
        throw new RuntimeException('Invalid email state.');
    }

    $userId = CurrentUser::id();
    (new LocalActionToken((new InstallationConfig($root . '/storage'))->appKey()))
        ->verify($token, 'email-state', $userId, $emailId, $state);
    $repository = new UserPreferencesRepository($root . '/storage');
    $preferences = $repository->load($userId);
    $preferences['email_states'] ??= [];

    if ($state === 'active') {
        unset($preferences['email_states'][$emailId]);
    } else {
        $preferences['email_states'][$emailId] = $state;
    }

    $repository->save($userId, $preferences);

    $stateRepository = new UserStateRepository($root . '/storage');
    $dashboard = $stateRepository->load($userId);
    $dashboard['emails'] = array_values(array_filter(
        $dashboard['emails'] ?? [],
        static fn (array $item): bool => (string) ($item['id'] ?? '') !== $emailId,
    ));
    $stateRepository->save($userId, $dashboard);

    header('Location: /?email_state_saved=1#emails', true, 302);
} catch (Throwable $exception) {
    header('Location: /?integration_error=' . rawurlencode($exception->getMessage()) . '#emails', true, 302);
}

exit;
