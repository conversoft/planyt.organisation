<?php

declare(strict_types=1);

namespace Planyt\Organisation\Http;

use RuntimeException;

final class CurlHttpClient implements HttpClientInterface
{
    public function get(string $url, array $headers = [], array $query = []): array
    {
        if ($query !== []) {
            $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query($query);
        }

        return $this->request('GET', $url, $headers);
    }

    public function postJson(string $url, array $headers, array $body): array
    {
        $headers['Content-Type'] = 'application/json';

        return $this->request('POST', $url, $headers, json_encode($body, JSON_THROW_ON_ERROR));
    }

    public function postForm(string $url, array $headers, array $body): array
    {
        $headers['Content-Type'] = 'application/x-www-form-urlencoded';

        return $this->request('POST', $url, $headers, http_build_query($body));
    }

    /** @param array<string, string> $headers @return array<string, mixed> */
    private function request(string $method, string $url, array $headers, ?string $body = null): array
    {
        $handle = curl_init($url);

        if ($handle === false) {
            throw new RuntimeException('Unable to initialize cURL.');
        }

        $headerLines = [];

        foreach ($headers as $name => $value) {
            $headerLines[] = $name . ': ' . $value;
        }

        curl_setopt_array($handle, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headerLines,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        if ($body !== null) {
            curl_setopt($handle, CURLOPT_POSTFIELDS, $body);
        }

        $response = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $error = curl_error($handle);

        if ($response === false) {
            throw new RuntimeException('HTTP request failed: ' . $error);
        }

        $decoded = json_decode($response, true);

        if ($status < 200 || $status >= 300) {
            $message = is_array($decoded) ? json_encode($decoded, JSON_UNESCAPED_SLASHES) : $response;
            throw new RuntimeException(sprintf('HTTP %d from %s: %s', $status, $url, $message));
        }

        if ($response === '') {
            return [];
        }

        if (!is_array($decoded)) {
            throw new RuntimeException(sprintf('Expected JSON response from %s.', $url));
        }

        return $decoded;
    }
}
