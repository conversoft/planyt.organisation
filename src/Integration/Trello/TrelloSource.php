<?php

declare(strict_types=1);

namespace Planyt\Organisation\Integration\Trello;

use Planyt\Organisation\Http\HttpClientInterface;

final class TrelloSource
{
    public function __construct(
        private readonly HttpClientInterface $http,
        private readonly TrelloConnection $connection,
    ) {
    }

    /** @return array<int, array<string, mixed>> */
    public function boards(string $userId, string $accountId): array
    {
        $token = $this->connection->accessToken($userId, $accountId);
        $boards = $this->http->get(
            'https://api.trello.com/1/members/me/boards',
            ['Authorization' => 'Bearer ' . $token],
            ['fields' => 'id,name,url,closed', 'filter' => 'open'],
        );

        return array_values(array_filter($boards, static fn (mixed $board): bool => is_array($board)));
    }

    /** @param array<int, string> $boardIds @return array<int, array<string, mixed>> */
    public function pull(string $userId, string $accountId, array $boardIds = []): array
    {
        $token = $this->connection->accessToken($userId, $accountId);
        $headers = ['Authorization' => 'Bearer ' . $token];
        $boards = $this->boards($userId, $accountId);

        if ($boardIds !== []) {
            $boards = array_values(array_filter(
                $boards,
                static fn (array $board): bool => in_array((string) ($board['id'] ?? ''), $boardIds, true),
            ));
        }

        $items = [];

        foreach ($boards as $board) {
            $boardId = (string) ($board['id'] ?? '');

            if ($boardId === '') {
                continue;
            }

            $cards = $this->http->get(
                'https://api.trello.com/1/boards/' . rawurlencode($boardId) . '/cards',
                $headers,
                ['filter' => 'open', 'fields' => 'id,name,desc,due,dueComplete,idMembers,idList,url,closed'],
            );

            foreach ($cards as $card) {
                if (!is_array($card)) {
                    continue;
                }

                $memberIds = array_map('strval', $card['idMembers'] ?? []);

                if (!in_array($accountId, $memberIds, true)) {
                    continue;
                }

                $items[] = [
                    'id' => 'trello:' . (string) ($card['id'] ?? ''),
                    'source' => 'trello',
                    'source_id' => (string) ($card['id'] ?? ''),
                    'title' => (string) ($card['name'] ?? ''),
                    'body' => (string) ($card['desc'] ?? ''),
                    'due' => (string) ($card['due'] ?? ''),
                    'due_complete' => (bool) ($card['dueComplete'] ?? false),
                    'board_id' => $boardId,
                    'board' => (string) ($board['name'] ?? ''),
                    'url' => (string) ($card['url'] ?? ''),
                    'context' => 'Board: ' . (string) ($board['name'] ?? ''),
                ];
            }
        }

        return $items;
    }
}
