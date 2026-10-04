<?php

declare(strict_types=1);

namespace Planyt\Organisation\Tests\PromptCompiler;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Planyt\Organisation\PromptCompiler\PromptCompiler;

final class PromptCompilerTest extends TestCase
{
    public function testEmailReplyPromptContainsSafetyBoundary(): void
    {
        $compiler = new PromptCompiler();

        $prompt = $compiler->compile([
            'title' => 'Rückfrage zum Angebot',
            'source' => 'gmail',
            'body' => 'Ist Oktober realistisch?',
            'context' => 'Trello: Angebot fertigstellen',
        ], 'email-reply');

        self::assertStringContainsString('Rückfrage zum Angebot', $prompt);
        self::assertStringContainsString('Erfinde keine Fakten', $prompt);
        self::assertStringContainsString('ausschließlich manuell', $prompt);
    }

    public function testUnknownIntentIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new PromptCompiler())->compile([
            'title' => 'Test',
        ], 'send-email');
    }
}
