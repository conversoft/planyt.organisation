<?php

declare(strict_types=1);

namespace Planyt\Organisation\Integration\Google;

use Planyt\Organisation\Http\HttpClientInterface;

final class DriveSource
{
    public function __construct(
        private readonly HttpClientInterface $http,
        private readonly GoogleConnection $connection,
    ) {
    }

    /** @return array<int, array<string, mixed>> */
    public function search(string $userId, string $accountId, string $term, int $pageSize = 20): array
    {
        $token = $this->connection->accessToken($userId, $accountId);
        $escaped = str_replace(["\\", "'"], ["\\\\", "\\'"], $term);
        $response = $this->http->get(
            'https://www.googleapis.com/drive/v3/files',
            ['Authorization' => 'Bearer ' . $token],
            [
                'q' => "name contains '" . $escaped . "' and trashed = false",
                'pageSize' => $pageSize,
                'fields' => 'files(id,name,mimeType,modifiedTime,webViewLink,owners(displayName,emailAddress))',
                'orderBy' => 'modifiedTime desc',
            ],
        );

        return array_values(array_filter(
            $response['files'] ?? [],
            static fn (mixed $file): bool => is_array($file),
        ));
    }
}
