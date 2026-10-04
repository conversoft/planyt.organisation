<?php

declare(strict_types=1);

use Planyt\Organisation\Auth\CurrentUser;
use Planyt\Organisation\Integration\IntegrationFactory;

$root = dirname(__DIR__, 2);
require $root . '/vendor/autoload.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new RuntimeException('PROAD-Verbindung benötigt POST.');
    }

    $apiKey = (string) ($_POST['api_key'] ?? '');
    $factory = new IntegrationFactory($root);

    if (!$factory->proadConfigured()) {
        throw new RuntimeException('PROAD ist für diese Installation noch nicht freigeschaltet.');
    }

    $factory->proadConnection()->connect(CurrentUser::id(), $apiKey);

    header('Location: /?connected=proad#connections', true, 302);
} catch (Throwable $exception) {
    header('Location: /?integration_error=' . rawurlencode($exception->getMessage()) . '#connections', true, 302);
}

exit;
