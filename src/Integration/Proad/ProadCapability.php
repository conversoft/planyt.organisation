<?php

declare(strict_types=1);

namespace Planyt\Organisation\Integration\Proad;

final class ProadCapability
{
    public const READ_PROJECTS = 'read_projects';
    public const BOOK_TIME = 'book_time';
    public const CREATE_PROJECT = 'create_project';
    public const CREATE_CONTACT = 'create_contact';
    public const CREATE_OFFER = 'create_offer';

    private function __construct()
    {
    }
}
