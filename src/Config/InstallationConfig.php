<?php

declare(strict_types=1);

namespace Planyt\Organisation\Config;

use RuntimeException;

final class InstallationConfig
{
    public function __construct(private readonly string $storageRoot)
    {
    }

    public function appKey(): string
    {
        $document = $this->load();

        if (isset($document['app_key']) && is_string($document['app_key']) && strlen($document['app_key']) >= 20) {
            return $document['app_key'];
        }

        $document['app_key'] = bin2hex(random_bytes(32));
        $this->save($document);

        return $document['app_key'];
    }

    /** @return array{client_id:string,client_secret:string,redirect_uri:string}|null */
    public function trello(): ?array
    {
        $document = $this->load();
        $trello = $document['trello'] ?? null;

        if (!is_array($trello)) {
            return null;
        }

        $clientId = trim((string) ($trello['client_id'] ?? ''));
        $clientSecret = trim((string) ($trello['client_secret'] ?? ''));
        $redirectUri = trim((string) ($trello['redirect_uri'] ?? ''));

        if ($clientId === '' || $clientSecret === '' || $redirectUri === '') {
            return null;
        }

        return [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => $redirectUri,
        ];
    }

    /** @param array{client_id:string,client_secret:string,redirect_uri:string} $config */
    public function saveTrello(array $config): void
    {
        $document = $this->load();
        $document['trello'] = $config;
        $this->save($document);
    }

    /** @return array<string, mixed> */
    private function load(): array
    {
        $path = $this->path();

        if (!is_file($path)) {
            return ['version' => 1];
        }

        $raw = file_get_contents($path);

        if ($raw === false) {
            throw new RuntimeException('Unable to read installation configuration.');
        }

        $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);

        return is_array($data) ? $data : ['version' => 1];
    }

    /** @param array<string, mixed> $document */
    private function save(array $document): void
    {
        $directory = dirname($this->path());

        if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new RuntimeException('Unable to create system storage.');
        }

        $json = json_encode(
            $document,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        );

        if (file_put_contents($this->path(), $json . PHP_EOL, LOCK_EX) === false) {
            throw new RuntimeException('Unable to write installation configuration.');
        }

        chmod($this->path(), 0600);
    }

    private function path(): string
    {
        return rtrim($this->storageRoot, '/') . '/system/config.json';
    }
}
