<?php

declare(strict_types=1);

use Planyt\Organisation\Auth\CurrentUser;
use Planyt\Organisation\Integration\Calendar\ExplicitUserAction;
use Planyt\Organisation\Integration\IntegrationFactory;
use Planyt\Organisation\Storage\UserPreferencesRepository;
use Planyt\Organisation\Storage\UserStateRepository;
use Planyt\Organisation\Workflow\SyncService;
use Planyt\Organisation\Workflow\UserActionSession;

$root = dirname(__DIR__, 2);
require $root . '/vendor/autoload.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new RuntimeException('Scheduling requires POST.');
    }

    $sourceId = (string) ($_POST['source_id'] ?? '');
    $title = trim((string) ($_POST['title'] ?? ''));
    $accountId = (string) ($_POST['account_id'] ?? '');
    $date = (string) ($_POST['date'] ?? '');
    $time = (string) ($_POST['time'] ?? '');
    $duration = max(15, min(480, (int) ($_POST['duration'] ?? 60)));
    $actionToken = (string) ($_POST['action_token'] ?? '');

    if ($sourceId === '' || $title === '' || $accountId === '' || $date === '' || $time === '') {
        throw new RuntimeException('Bitte Datum und Uhrzeit auswählen.');
    }

    $action = (new UserActionSession())->consume($actionToken, 'schedule', $sourceId);
    $start = new DateTimeImmutable($date . 'T' . $time . ':00');
    $end = $start->modify('+' . $duration . ' minutes');

    $factory = new IntegrationFactory($root);
    $userId = CurrentUser::id();

    $factory->calendar()->createPlanningBlock(
        $userId,
        $accountId,
        [
            'summary' => $title,
            'description' => 'Persönlicher Arbeitsblock aus Planyt Organisation. Die Trello-Karte wird dadurch nicht verändert.',
            'start' => $start->format(DATE_ATOM),
            'end' => $end->format(DATE_ATOM),
            'source' => 'trello',
            'source_id' => $sourceId,
        ],
        new ExplicitUserAction($actionToken, time()),
    );

    (new SyncService(
        $factory,
        new UserStateRepository($root . '/storage'),
        new UserPreferencesRepository($root . '/storage'),
    ))->sync($userId);

    header('Location: /?scheduled=1', true, 302);
} catch (Throwable $exception) {
    header('Location: /?integration_error=' . rawurlencode($exception->getMessage()), true, 302);
}

exit;
