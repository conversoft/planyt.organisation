<?php

declare(strict_types=1);

namespace Planyt\Organisation\Workflow;

use DateTimeImmutable;
use DateTimeZone;
use Planyt\Organisation\Integration\IntegrationFactory;
use Planyt\Organisation\Storage\UserPreferencesRepository;
use Planyt\Organisation\Storage\UserStateRepository;

final class SyncService
{
    public function __construct(
        private readonly IntegrationFactory $integrations,
        private readonly UserStateRepository $states,
        private readonly UserPreferencesRepository $preferences,
    ) {
    }

    /** @return array<string, mixed> */
    public function sync(string $userId, string $timezone = 'Europe/Berlin'): array
    {
        $prefs = $this->preferences->load($userId);
        $trelloItems = [];
        $emails = [];
        $events = [];
        $boardsByAccount = [];

        $trelloAccounts = $this->integrations->trelloConfigured()
            ? $this->integrations->trelloConnection()->accounts($userId)
            : [];

        foreach ($trelloAccounts as $account) {
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

            $boardsByAccount[$accountId] = $this->integrations->trello()->boards($userId, $accountId);
            $trelloItems = array_merge(
                $trelloItems,
                $this->integrations->trello()->pull($userId, $accountId, $selectedBoards),
            );
        }

        $zone = new DateTimeZone($timezone);
        $now = new DateTimeImmutable('now', $zone);
        $rangeStart = $now->setTime(0, 0);
        $rangeEnd = $rangeStart->modify('+30 days');

        $googleAccounts = $this->integrations->googleConnection()->accounts($userId);

        foreach ($googleAccounts as $account) {
            $accountId = (string) ($account['account_id'] ?? '');

            if ($accountId === '') {
                continue;
            }

            $emails = array_merge(
                $emails,
                $this->integrations->gmail()->pull($userId, $accountId),
            );

            foreach ($this->integrations->calendar()->events($userId, $accountId, $rangeStart, $rangeEnd) as $event) {
                $event['_account'] = $accountId;
                $events[] = $event;
            }
        }

        $scheduledSourceIds = [];

        foreach ($events as $event) {
            $sourceId = (string) ($event['extendedProperties']['private']['planytSourceId'] ?? '');

            if ($sourceId !== '') {
                $scheduledSourceIds[$sourceId] = true;
            }
        }

        $taskStates = is_array($prefs['task_states'] ?? null) ? $prefs['task_states'] : [];
        $emailStates = is_array($prefs['email_states'] ?? null) ? $prefs['email_states'] : [];

        $unscheduled = array_values(array_filter(
            $trelloItems,
            static function (array $item) use ($scheduledSourceIds, $taskStates): bool {
                $sourceId = (string) ($item['source_id'] ?? '');

                $localState = (string) ($taskStates[$sourceId] ?? '');

                return !($item['due_complete'] ?? false)
                    && $sourceId !== ''
                    && !in_array($localState, ['done', 'irrelevant'], true)
                    && !isset($scheduledSourceIds[$sourceId]);
            },
        ));

        $todayEnd = $rangeStart->modify('+1 day');
        $today = [];

        foreach ($events as $event) {
            $startValue = (string) ($event['start']['dateTime'] ?? $event['start']['date'] ?? '');

            if ($startValue === '') {
                continue;
            }

            $start = new DateTimeImmutable($startValue, $zone);

            if ($start < $rangeStart || $start >= $todayEnd) {
                continue;
            }

            $endValue = (string) ($event['end']['dateTime'] ?? $event['end']['date'] ?? $startValue);
            $end = new DateTimeImmutable($endValue, $zone);
            $allDay = isset($event['start']['date']);

            $today[] = [
                'id' => (string) ($event['id'] ?? ''),
                'source' => 'calendar',
                'account' => (string) ($event['_account'] ?? ''),
                'time' => $allDay ? 'Ganztägig' : $start->format('H:i') . '–' . $end->format('H:i'),
                'title' => (string) ($event['summary'] ?? '(ohne Titel)'),
                'meta' => (string) ($event['_account'] ?? ''),
                'html_link' => (string) ($event['htmlLink'] ?? ''),
            ];
        }

        usort(
            $today,
            static fn (array $a, array $b): int => strcmp((string) $a['time'], (string) $b['time']),
        );

        $state = [
            'user' => ['id' => $userId, 'timezone' => $timezone],
            'updated_at' => (new DateTimeImmutable())->format(DATE_ATOM),
            'today' => $today,
            'unscheduled' => $unscheduled,
            'emails' => array_values(array_filter(
                $emails,
                static function (array $mail) use ($emailStates): bool {
                    $id = (string) ($mail['id'] ?? '');

                    return (bool) ($mail['needsReply'] ?? false)
                        && $id !== ''
                        && (string) ($emailStates[$id] ?? '') !== 'hidden';
                },
            )),
            'trello' => $trelloItems,
            'boards' => $boardsByAccount,
        ];

        $this->states->save($userId, $state);

        return $state;
    }
}
