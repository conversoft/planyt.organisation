<?php

declare(strict_types=1);

use Planyt\Organisation\PromptCompiler\PromptCompiler;
use Planyt\Organisation\Storage\JsonDashboardRepository;

require dirname(__DIR__) . '/vendor/autoload.php';

$repository = new JsonDashboardRepository();
$data = $repository->load(dirname(__DIR__) . '/resources/demo/dashboard.json');
$compiler = new PromptCompiler();

$prompt = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $itemId = (string) ($_POST['item_id'] ?? '');
    $intent = (string) ($_POST['intent'] ?? '');
    $items = array_merge($data['unscheduled'] ?? [], $data['emails'] ?? []);
    $selected = null;

    foreach ($items as $item) {
        if (($item['id'] ?? null) === $itemId) {
            $selected = $item;
            break;
        }
    }

    if ($selected !== null) {
        try {
            $prompt = $compiler->compile($selected, $intent);
        } catch (Throwable $exception) {
            $error = $exception->getMessage();
        }
    }
}

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Planyt Organisation</title>
    <link rel="stylesheet" href="/assets/app.css">
</head>
<body>
<div class="shell">
    <aside class="sidebar">
        <div class="brand">planyt<span>.</span></div>
        <nav>
            <a class="active" href="#">Heute</a>
            <a href="#planung">Noch terminieren</a>
            <a href="#emails">E-Mails</a>
        </nav>
        <div class="source-status">
            <p><strong>Quellen</strong></p>
            <p>● Trello <small>read-only</small></p>
            <p>● Gmail <small>read-only</small></p>
            <p>● Calendar <small>read/write</small></p>
        </div>
    </aside>
    <main>
        <header class="topbar">
            <div>
                <p class="eyebrow">Persönliche Arbeitsübersicht</p>
                <h1>Guten Abend, <?= h((string) ($data['user']['name'] ?? '')) ?></h1>
            </div>
            <div class="badge">Prototype 0.1</div>
        </header>

        <section class="hero">
            <p class="eyebrow">Heute im Blick</p>
            <h2><?= count($data['today'] ?? []) ?> Termine · <?= count($data['unscheduled'] ?? []) ?> noch zu planen · <?= count($data['emails'] ?? []) ?> Mails mit Handlungsbedarf</h2>
        </section>

        <section class="grid">
            <div class="panel">
                <div class="panel-title"><h3>Heute</h3><span>Google Calendar</span></div>
                <?php foreach ($data['today'] ?? [] as $item): ?>
                    <article class="timeline-item">
                        <time><?= h((string) $item['time']) ?></time>
                        <div><strong><?= h((string) $item['title']) ?></strong><p><?= h((string) $item['meta']) ?></p></div>
                    </article>
                <?php endforeach; ?>
            </div>

            <div class="panel warning" id="planung">
                <div class="panel-title"><h3>Noch terminieren</h3><span>Entscheidung nötig</span></div>
                <?php foreach ($data['unscheduled'] ?? [] as $item): ?>
                    <article class="work-item">
                        <div class="source trello">Trello</div>
                        <strong><?= h((string) $item['title']) ?></strong>
                        <p><?= h((string) $item['context']) ?></p>
                        <?php if (($item['due'] ?? '') !== ''): ?>
                            <p class="due">Fällig: <?= h((string) $item['due']) ?></p>
                        <?php else: ?>
                            <p class="due critical">Kein Termin vorhanden – bitte terminieren.</p>
                        <?php endif; ?>
                        <div class="actions">
                            <button type="button" disabled>Termin planen</button>
                            <form method="post">
                                <input type="hidden" name="item_id" value="<?= h((string) $item['id']) ?>">
                                <input type="hidden" name="intent" value="task-help">
                                <button class="secondary" type="submit">Prompt erstellen</button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="panel" id="emails">
            <div class="panel-title"><h3>E-Mails, die Aufmerksamkeit brauchen</h3><span>nur gelesen – Versand nicht möglich</span></div>
            <div class="mail-grid">
                <?php foreach ($data['emails'] ?? [] as $item): ?>
                    <article class="mail-card">
                        <div class="source gmail">Gmail · <?= h((string) $item['account']) ?></div>
                        <strong><?= h((string) $item['title']) ?></strong>
                        <p>Von <?= h((string) $item['from']) ?></p>
                        <blockquote><?= h((string) $item['body']) ?></blockquote>
                        <form method="post">
                            <input type="hidden" name="item_id" value="<?= h((string) $item['id']) ?>">
                            <input type="hidden" name="intent" value="email-reply">
                            <button class="secondary" type="submit">Antwort-Prompt erstellen</button>
                        </form>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <?php if ($prompt !== null || $error !== null): ?>
            <section class="panel prompt-panel" id="prompt">
                <div class="panel-title"><h3>Prompt Compiler</h3><span>keine API · kein Versand</span></div>
                <?php if ($error !== null): ?>
                    <p><?= h($error) ?></p>
                <?php else: ?>
                    <textarea id="compiledPrompt" readonly><?= h((string) $prompt) ?></textarea>
                    <button type="button" data-copy-prompt>Prompt kopieren</button>
                    <p class="hint">Planyt kopiert nur Text in die Zwischenablage. Es sendet nichts an ChatGPT oder Gmail.</p>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <footer>Trello und Gmail bleiben read-only. Kalenderplanung verändert niemals den Status einer Trello-Karte.</footer>
    </main>
</div>
<script src="/assets/app.js"></script>
</body>
</html>
