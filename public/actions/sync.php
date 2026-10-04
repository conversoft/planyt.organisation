<?php

declare(strict_types=1);

use Planyt\Organisation\Config\DotEnv;
use Planyt\Organisation\Config\Env;
use Planyt\Organisation\Integration\IntegrationFactory;
use Planyt\Organisation\Storage\UserPreferencesRepository;
use Planyt\Organisation\Storage\UserStateRepository;
use Planyt\Organisation\Workflow\SyncService;

$root = dirname(__DIR__, 2);
require $root . '/vendor/autoload.php';
DotEnv::load($root . '/.env');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new RuntimeException('Sync requires POST.');
    }

    $userId = Env::get('PLANYT_USER_ID', 'demo') ?? 'demo';
    $sync = new SyncService(
        new IntegrationFactory($root),
        new UserStateRepository($root . '/storage'),
        new UserPreferencesRepository($root . '/storage'),
    );
    $sync->sync($userId);

    header('Location: /?synced=1', true, 302);
} catch (Throwable $exception) {
    header('Location: /?integration_error=' . rawurlencode($exception->getMessage()), true, 302);
}

exit;
