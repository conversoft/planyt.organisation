<?php

declare(strict_types=1);

use Planyt\Organisation\Config\DotEnv;
use Planyt\Organisation\Config\Env;
use Planyt\Organisation\Integration\IntegrationFactory;
use Planyt\Organisation\OAuth\OAuthSession;

$root = dirname(__DIR__, 3);
require $root . '/vendor/autoload.php';
DotEnv::load($root . '/.env');

try {
    $state = (string) ($_GET['state'] ?? '');
    $code = (string) ($_GET['code'] ?? '');

    if ($state === '' || $code === '') {
        throw new RuntimeException('Google callback is missing state or code.');
    }

    (new OAuthSession())->consume('google', $state);

    $factory = new IntegrationFactory($root);
    $token = $factory->googleOAuth()->exchangeCode($code);
    $factory->googleConnection()->storeNew(
        Env::get('PLANYT_USER_ID', 'demo') ?? 'demo',
        $token,
    );

    header('Location: /?connected=google', true, 302);
} catch (Throwable $exception) {
    header('Location: /?integration_error=' . rawurlencode($exception->getMessage()), true, 302);
}

exit;
