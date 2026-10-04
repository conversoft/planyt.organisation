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
        throw new RuntimeException('Trello callback is missing state or code.');
    }

    $payload = (new OAuthSession())->consume('trello', $state);
    $verifier = (string) ($payload['code_verifier'] ?? '');

    if ($verifier === '') {
        throw new RuntimeException('Trello PKCE verifier is missing.');
    }

    $factory = new IntegrationFactory($root);
    $token = $factory->trelloOAuth()->exchangeCode($code, $verifier);
    $factory->trelloConnection()->storeNew(
        Env::get('PLANYT_USER_ID', 'demo') ?? 'demo',
        $token,
    );

    header('Location: /?connected=trello', true, 302);
} catch (Throwable $exception) {
    header('Location: /?integration_error=' . rawurlencode($exception->getMessage()), true, 302);
}

exit;
