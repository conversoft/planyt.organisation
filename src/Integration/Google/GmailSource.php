<?php

declare(strict_types=1);

namespace Planyt\Organisation\Integration\Google;

use Planyt\Organisation\Http\HttpClientInterface;

final class GmailSource
{
    public function __construct(
        private readonly HttpClientInterface $http,
        private readonly GoogleConnection $connection,
    ) {
    }

    /** @return array<int, array<string, mixed>> */
    public function pull(string $userId, string $accountId, int $maxResults = 30): array
    {
        $accessToken = $this->connection->accessToken($userId, $accountId);
        $headers = ['Authorization' => 'Bearer ' . $accessToken];
        $list = $this->http->get(
            'https://gmail.googleapis.com/gmail/v1/users/me/threads',
            $headers,
            ['q' => 'in:inbox newer_than:30d', 'maxResults' => $maxResults],
        );

        $items = [];

        foreach ($list['threads'] ?? [] as $threadRef) {
            $thread = $this->http->get(
                'https://gmail.googleapis.com/gmail/v1/users/me/threads/' . rawurlencode((string) $threadRef['id']),
                $headers,
                ['format' => 'metadata', 'metadataHeaders' => 'From,Subject,Date'],
            );
            $messages = $thread['messages'] ?? [];

            if ($messages === []) {
                continue;
            }

            $last = $messages[array_key_last($messages)];
            $headersMap = $this->headers($last['payload']['headers'] ?? []);
            $from = $headersMap['from'] ?? '';
            $subject = $headersMap['subject'] ?? '(ohne Betreff)';
            $labels = $last['labelIds'] ?? [];
            $fromSelf = str_contains(strtolower($from), strtolower($accountId));
            $sentBySelf = in_array('SENT', $labels, true);

            $items[] = [
                'id' => 'gmail:' . $accountId . ':' . (string) $thread['id'],
                'source' => 'gmail',
                'account' => $accountId,
                'thread_id' => (string) $thread['id'],
                'title' => $subject,
                'from' => $from,
                'received' => $headersMap['date'] ?? '',
                'body' => (string) ($last['snippet'] ?? ''),
                'needsReply' => !$fromSelf && !$sentBySelf,
                'context' => 'Gmail thread ' . (string) $thread['id'],
            ];
        }

        return $items;
    }

    /** @param array<int, array<string, mixed>> $headers @return array<string, string> */
    private function headers(array $headers): array
    {
        $result = [];

        foreach ($headers as $header) {
            $name = strtolower((string) ($header['name'] ?? ''));

            if ($name !== '') {
                $result[$name] = (string) ($header['value'] ?? '');
            }
        }

        return $result;
    }
}
