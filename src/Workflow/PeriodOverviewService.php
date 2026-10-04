<?php

declare(strict_types=1);

namespace Planyt\Organisation\Workflow;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use Planyt\Organisation\Integration\IntegrationFactory;
use Planyt\Organisation\Storage\UserPreferencesRepository;

final class PeriodOverviewService
{
    public function __construct(
        private readonly IntegrationFactory $integrations,
        private readonly UserPreferencesRepository $preferences,
    ) {
    }

    /** @return array{from:string,to:string,sent_mails:array<int,array<string,mixed>>,trello:array<int,array<string,mixed>>,calendar:array<int,array<string,mixed>>} */
    public function load(string $userId, string $fromDate, string $toDate, string $timezone = 'Europe/Berlin'): array
    {
        $zone = new DateTimeZone($timezone);
        $from = DateTimeImmutable::createFromFormat('!Y-m-d', $fromDate, $zone);
        $toInclusive = DateTimeImmutable::createFromFormat('!Y-m-d', $toDate, $zone);

        if ($from === false || $toInclusive === false || $toInclusive < $from) {
            throw new InvalidArgumentException('Bitte einen gültigen Zeitraum auswählen.');
        }

        $to = $toInclusive->modify('+1 day');
        $prefs = $this->preferences->load($userId);
        $sentMails = [];
        $trelloItems = [];
        $calendarItems = [];

        foreach ($this->integrations->googleConnection()->accounts($userId) as $account) {
            $accountId = (string) ($account['account_id'] ?? '');

            if ($accountId === '') {
                continue;
            }

            $sentMails = array_merge(
                $sentMails,
                $this->integrations->gmail()->sentBetween($userId, $accountId, $from, $to),
            );

            foreach ($this->integrations->calendar()->events($userId, $accountId, $from, $to) as $event) {
                $event['_account'] = $accountId;
                $calendarItems[] = $event;
            }
        }

        if ($this->integrations->trelloConfigured()) {
            foreach ($this->integrations->trelloConnection()->accounts($userId) as $account) {
                $accountId = (string) ($account['account_id'] ?? '');

                if ($accountId === '') {
                    continue;
                }

                $selectedBoards = array_key_exists($accountId, $prefs['trello_boards'] ?? [])
                    ? ($prefs['trello_boards'][$accountId] ?? [])
                    : null;
                $selectedBoards = is_array($selectedBoards)
                    ? array_values(array_map('strval', $selectedBoards))
                    : null;

                $trelloItems = array_merge(
                    $trelloItems,
                    $this->integrations->trello()->activityBetween(
                        $userId,
                        $accountId,
                        $from,
                        $to,
                        $selectedBoards,
                    ),
                );
            }
        }

        usort(
            $sentMails,
            static fn (array $a, array $b): int => ($b['internal_date'] ?? 0) <=> ($a['internal_date'] ?? 0),
        );
        usort(
            $trelloItems,
            static fn (array $a, array $b): int => strcmp(
                (string) ($b['last_activity'] ?? ''),
                (string) ($a['last_activity'] ?? ''),
            ),
        );
        usort(
            $calendarItems,
            static fn (array $a, array $b): int => strcmp(
                (string) ($a['start']['dateTime'] ?? $a['start']['date'] ?? ''),
                (string) ($b['start']['dateTime'] ?? $b['start']['date'] ?? ''),
            ),
        );

        return [
            'from' => $fromDate,
            'to' => $toDate,
            'sent_mails' => $sentMails,
            'trello' => $trelloItems,
            'calendar' => $calendarItems,
        ];
    }
}
