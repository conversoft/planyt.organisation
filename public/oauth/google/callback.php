<?php

declare(strict_types=1);

use Planyt\Organisation\Auth\CurrentUser;
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

    (new OAuthSession())->consume('google', $state);

    $factory = new IntegrationFactory($root);
    $token = $factory->googleOAuth()->exchangeCode($code);
    $factory->googleConnection()->storeNew(CurrentUser::id(), $token);

    header('Location: /?connected=google#connections', true, 302);
} catch (Throwable $exception) {
    header('Location: /?integration_error=' . rawurlencode($exception->getMessage()) . '#connections', true, 302);
}

exit;
