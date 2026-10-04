<?php

declare(strict_types=1);

namespace Planyt\Organisation\Http;

interface HttpClientInterface
{
    /** @param array<string, string> $headers @param array<string, scalar|null> $query @return array<string, mixed> */
    public function get(string $url, array $headers = [], array $query = []): array;

    /** @param array<string, string> $headers @param array<string, mixed> $body @return array<string, mixed> */
    public function postJson(string $url, array $headers, array $body): array;

    /** @param array<string, string> $headers @param array<string, scalar|null> $body @return array<string, mixed> */
    public function postForm(string $url, array $headers, array $body): array;
}
