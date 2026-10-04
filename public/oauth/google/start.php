<?php

declare(strict_types=1);

use Planyt\Organisation\Auth\CurrentUser;
use Planyt\Organisation\Integration\IntegrationFactory;
use Planyt\Organisation\OAuth\OAuthSession;

$root = dirname(__DIR__, 3);
require $root . '/vendor/autoload.php';

try {
    $factory = new IntegrationFactory($root);

    if (!$factory->googleConfigured()) {
        throw new RuntimeException('Google ist für diese Installation noch nicht freigeschaltet.');
    }

    $state = (new OAuthSession())->begin('google', [
        'started_at' => time(),
        'user_id' => CurrentUser::id(),
    ]);

    header('Location: ' . $factory->googleOAuth()->authorizationUrl($state), true, 302);
} catch (Throwable $exception) {
    header('Location: /?integration_error=' . rawurlencode($exception->getMessage()) . '#connections', true, 302);
}

exit;
