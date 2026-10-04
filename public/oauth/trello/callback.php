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
        throw new RuntimeException('Trello-Anmeldung konnte nicht abgeschlossen werden.');
    }

    $payload = (new OAuthSession())->consume('trello', $state);
    $verifier = (string) ($payload['code_verifier'] ?? '');
    $userId = (string) ($payload['user_id'] ?? '');

    if ($verifier === '' || $userId === '') {
        throw new RuntimeException('Trello-Anmeldung ist abgelaufen. Bitte erneut versuchen.');
    }

    $factory = new IntegrationFactory($root);
    $token = $factory->trelloOAuth()->exchangeCode($code, $verifier);
    $factory->trelloConnection()->storeNew($userId, $token);

    header('Location: /?connected=trello#connections', true, 302);
} catch (Throwable $exception) {
    header('Location: /?integration_error=' . rawurlencode($exception->getMessage()), true, 302);
}

exit;
