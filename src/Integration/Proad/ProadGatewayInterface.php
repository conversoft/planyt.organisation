<?php

declare(strict_types=1);

namespace Planyt\Organisation\Integration\Proad;

interface ProadGatewayInterface
{
    /** @return array<int, string> */
    public function capabilities(string $userId): array;

    /** @return array<int, array<string, mixed>> */
    public function projects(string $userId): array;

    /** @param array<string, mixed> $entry */
    public function bookTime(string $userId, array $entry): string;

    /** @param array<string, mixed> $project */
    public function createProject(string $userId, array $project): string;

    /** @param array<string, mixed> $contact */
    public function createContact(string $userId, array $contact): string;

    /** @param array<string, mixed> $offer */
    public function createOffer(string $userId, array $offer): string;
}
