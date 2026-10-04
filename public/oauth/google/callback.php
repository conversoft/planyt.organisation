<?php

declare(strict_types=1);

use Planyt\Organisation\Integration\IntegrationFactory;
use Planyt\Organisation\OAuth\OAuthSession;

$root = dirname(__DIR__, 3);
require $root . '/vendor/autoload.php';

try {
    $state = (string) ($_GET['state'] ?? '');
    $code = (string) ($_GET['code'] ?? '');

    if ($state === '' || $code === '') {
        throw new RuntimeException('Google-Anmeldung konnte nicht abgeschlossen werden.');
    }

    $payload = (new OAuthSession())->consume('google', $state);
    $userId = (string) ($payload['user_id'] ?? '');

    if ($userId === '') {
        throw new RuntimeException('Google-Anmeldung ist keinem Nutzer zugeordnet. Bitte erneut versuchen.');
    }

    $factory = new IntegrationFactory($root);
    $token = $factory->googleOAuth()->exchangeCode($code);
    $factory->googleConnection()->storeNew($userId, $token);

    header('Location: /?connected=google#connections', true, 302);
} catch (Throwable $exception) {
    header('Location: /?integration_error=' . rawurlencode($exception->getMessage()) . '#connections', true, 302);
}

exit;
