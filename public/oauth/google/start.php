<?php

declare(strict_types=1);

use Planyt\Organisation\Config\DotEnv;
use Planyt\Organisation\Integration\IntegrationFactory;
use Planyt\Organisation\OAuth\OAuthSession;

$root = dirname(__DIR__, 3);
require $root . '/vendor/autoload.php';
DotEnv::load($root . '/.env');

$session = new OAuthSession();
$state = $session->begin('google', ['started_at' => time()]);
$factory = new IntegrationFactory($root);

header('Location: ' . $factory->googleOAuth()->authorizationUrl($state), true, 302);
exit;
