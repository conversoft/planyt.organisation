<?php

declare(strict_types=1);

use Planyt\Organisation\Config\DotEnv;
use Planyt\Organisation\Integration\IntegrationFactory;
use Planyt\Organisation\OAuth\OAuthSession;
use Planyt\Organisation\OAuth\Pkce;

$root = dirname(__DIR__, 3);
require $root . '/vendor/autoload.php';
DotEnv::load($root . '/.env');

$pkce = Pkce::create();
$session = new OAuthSession();
$state = $session->begin('trello', [
    'started_at' => time(),
    'code_verifier' => $pkce['verifier'],
]);

$factory = new IntegrationFactory($root);
header('Location: ' . $factory->trelloOAuth()->authorizationUrl($state, $pkce['challenge']), true, 302);
exit;
