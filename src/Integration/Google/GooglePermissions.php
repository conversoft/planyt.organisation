<?php

declare(strict_types=1);

namespace Planyt\Organisation\Integration\Google;

final class GooglePermissions
{
    public const REQUIRED_SCOPES = [
        'https://www.googleapis.com/auth/gmail.readonly',
        'https://www.googleapis.com/auth/drive.readonly',
        'https://www.googleapis.com/auth/calendar.events.owned',
    ];

    private function __construct()
    {
    }
}
