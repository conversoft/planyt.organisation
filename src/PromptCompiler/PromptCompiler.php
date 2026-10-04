<?php

declare(strict_types=1);

namespace Planyt\Organisation\PromptCompiler;

use InvalidArgumentException;

final class PromptCompiler
{
    /** @param array<string, mixed> $item */
    public function compile(array $item, string $intent): string
    {
        $title = trim((string) ($item['title'] ?? ''));

        if ($title === '') {
            throw new InvalidArgumentException('A title is required to compile a prompt.');
        }

        $source = (string) ($item['source'] ?? 'unknown');
        $context = trim((string) ($item['context'] ?? ''));
        $due = trim((string) ($item['due'] ?? ''));
        $body = trim((string) ($item['body'] ?? ''));

        $instruction = match ($intent) {
            'email-reply' => 'Formuliere einen professionellen, freundlichen Antwortentwurf. Erfinde keine Fakten. Markiere klar, welche Informationen für eine vollständige Antwort fehlen.',
            'task-help' => 'Hilf mir, diese Aufgabe strukturiert zu bearbeiten. Schlage konkrete nächste Schritte vor und erfinde keine fehlenden Fakten.',
            'summary' => 'Fasse den Kontext prägnant zusammen und nenne offene Punkte sowie konkrete nächste Schritte.',
            default => throw new InvalidArgumentException(sprintf('Unsupported prompt intent: %s', $intent)),
        };

        $parts = [
            'Ich arbeite mit Planyt Organisation an folgendem Arbeitselement.',
            '',
            'TITEL',
            $title,
            '',
            'QUELLE',
            $source,
        ];

        if ($due !== '') {
            $parts[] = '';
            $parts[] = 'FRIST / FÄLLIGKEIT';
            $parts[] = $due;
        }

        if ($body !== '') {
            $parts[] = '';
            $parts[] = 'INHALT';
            $parts[] = $body;
        }

        if ($context !== '') {
            $parts[] = '';
            $parts[] = 'ZUSÄTZLICHER KONTEXT';
            $parts[] = $context;
        }

        $parts[] = '';
        $parts[] = 'AUFGABE AN DICH';
        $parts[] = $instruction;
        $parts[] = '';
        $parts[] = 'WICHTIG';
        $parts[] = 'Der Text ist nur ein Vorschlag. Ich prüfe ihn selbst und führe externe Kommunikation ausschließlich manuell im Originalsystem aus.';

        return implode(PHP_EOL, $parts);
    }
}
