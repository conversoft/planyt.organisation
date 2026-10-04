<?php

declare(strict_types=1);

namespace Planyt\Organisation\Integration\Google;

use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;
use Planyt\Organisation\Http\HttpClientInterface;
use Planyt\Organisation\Integration\Calendar\ExplicitUserAction;

final class CalendarClient
{
    public function __construct(
        private readonly HttpClientInterface $http,
        private readonly GoogleConnection $connection,
    ) {
    }

    /** @return array<int, array<string, mixed>> */
    public function events(
        string $userId,
        string $accountId,
        DateTimeInterface $from,
        DateTimeInterface $to,
        string $calendarId = 'primary',
    ): array {
        $token = $this->connection->accessToken($userId, $accountId);
        $response = $this->http->get(
            'https://www.googleapis.com/calendar/v3/calendars/' . rawurlencode($calendarId) . '/events',
            ['Authorization' => 'Bearer ' . $token],
            [
                'timeMin' => $from->format(DATE_RFC3339),
                'timeMax' => $to->format(DATE_RFC3339),
                'singleEvents' => 'true',
                'orderBy' => 'startTime',
                'maxResults' => 100,
            ],
        );

        return array_values(array_filter(
            $response['items'] ?? [],
            static fn (mixed $item): bool => is_array($item),
        ));
    }

    /** @param array<string, mixed> $event */
    public function createPlanningBlock(
        string $userId,
        string $accountId,
        array $event,
        ExplicitUserAction $confirmation,
        string $calendarId = 'primary',
    ): string {
        $summary = trim((string) ($event['summary'] ?? ''));
        $start = (string) ($event['start'] ?? '');
        $end = (string) ($event['end'] ?? '');

        if ($summary === '' || $start === '' || $end === '') {
            throw new InvalidArgumentException('Calendar planning blocks require summary, start and end.');
        }

        $startAt = new DateTimeImmutable($start);
        $endAt = new DateTimeImmutable($end);

        if ($endAt <= $startAt) {
            throw new InvalidArgumentException('Calendar planning block end must be after start.');
        }

        $token = $this->connection->accessToken($userId, $accountId);
        $response = $this->http->postJson(
            'https://www.googleapis.com/calendar/v3/calendars/' . rawurlencode($calendarId) . '/events',
            [
                'Authorization' => 'Bearer ' . $token,
                'X-Planyt-User-Action' => $confirmation->actionId,
            ],
            [
                'summary' => $summary,
                'description' => (string) ($event['description'] ?? 'Planyt Organisation Arbeitsblock'),
                'start' => ['dateTime' => $startAt->format(DATE_RFC3339)],
                'end' => ['dateTime' => $endAt->format(DATE_RFC3339)],
                'extendedProperties' => [
                    'private' => [
                        'planytSource' => (string) ($event['source'] ?? 'manual'),
                        'planytSourceId' => (string) ($event['source_id'] ?? ''),
                    ],
                ],
            ],
        );

        return (string) ($response['id'] ?? '');
    }
}
