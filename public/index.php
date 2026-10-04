<?php

declare(strict_types=1);

use Planyt\Organisation\Auth\CurrentUser;
use Planyt\Organisation\Auth\ExistingGoogleLogin;
use Planyt\Organisation\Integration\IntegrationFactory;
use Planyt\Organisation\Config\InstallationConfig;
use Planyt\Organisation\PromptCompiler\PromptCompiler;
use Planyt\Organisation\Security\LocalActionToken;
use Planyt\Organisation\Storage\JsonDashboardRepository;
use Planyt\Organisation\Storage\UserStateRepository;
use Planyt\Organisation\Storage\UserPreferencesRepository;
use Planyt\Organisation\Workflow\UserActionSession;

$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';
$userId = CurrentUser::id();
$compiler = new PromptCompiler();
$preferences = (new UserPreferencesRepository($root . '/storage'))->load($userId);
$localActions = new LocalActionToken((new InstallationConfig($root . '/storage'))->appKey());
$prompt = null;
$error = $_GET['integration_error'] ?? null;
$factory = null;
$googleAccounts = [];
$trelloAccounts = [];
$liveMode = true;

if ($liveMode) {
    try {
        $factory = new IntegrationFactory($root);
        (new ExistingGoogleLogin($factory->googleConnection()))->importFromSession($userId);
        $googleAccounts = $factory->googleConnection()->accounts($userId);
        $trelloAccounts = $factory->trelloConfigured()
            ? $factory->trelloConnection()->accounts($userId)
            : [];
        $data = (new UserStateRepository($root . '/storage'))->load($userId);

        $taskStates = is_array($preferences['task_states'] ?? null) ? $preferences['task_states'] : [];
        $data['unscheduled'] = array_values(array_filter(
            $data['unscheduled'] ?? [],
            static function (array $item) use ($taskStates): bool {
                $sourceId = (string) ($item['source_id'] ?? '');

                return $sourceId !== ''
                    && !in_array((string) ($taskStates[$sourceId] ?? ''), ['done', 'irrelevant'], true);
            },
        ));
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
        $data = (new JsonDashboardRepository())->load($root . '/resources/demo/dashboard.json');
        $liveMode = false;
    }
} else {
    $data = (new JsonDashboardRepository())->load($root . '/resources/demo/dashboard.json');
}

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

$actions = new UserActionSession();

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
            <a href="#connections">Verbindungen</a>
        </nav>
        <div class="source-status">
            <p><strong>Systemgrenzen</strong></p>
            <p>● Trello <small>read-only</small></p>
            <p>● Gmail <small>read-only</small></p>
            <p>● Drive <small>read-only</small></p>
            <p>● Calendar <small>read/write</small></p>
        </div>
    </aside>

    <main>
        <header class="topbar">
            <div>
                <p class="eyebrow">Persönliche Arbeitsübersicht</p>
                <h1><?= $liveMode ? 'Deine Organisation' : 'Prototyp-Demo' ?></h1>
            </div>
            <div class="badge"><?= $liveMode ? 'Live' : 'Demo' ?></div>
        </header>

        <?php if ($error !== null): ?>
            <div class="notice error"><?= h((string) $error) ?></div>
        <?php elseif (isset($_GET['synced'])): ?>
            <div class="notice">Daten wurden neu eingelesen.</div>
        <?php elseif (isset($_GET['scheduled'])): ?>
            <div class="notice">Kalenderblock wurde angelegt. Trello blieb unverändert.</div>
        <?php elseif (isset($_GET['task_state_saved'])): ?>
            <div class="notice">Persönlicher Planyt-Status gespeichert. Trello blieb unverändert.</div>
        <?php elseif (isset($_GET['email_state_saved'])): ?>
            <div class="notice">E-Mail in Planyt ausgeblendet. Gmail blieb unverändert.</div>
        <?php endif; ?>

        <section class="hero">
            <p class="eyebrow">Heute im Blick</p>
            <h2><?= count($data['today'] ?? []) ?> Termine · <?= count($data['unscheduled'] ?? []) ?> noch zu planen · <?= count($data['emails'] ?? []) ?> Mails mit Handlungsbedarf</h2>
            <?php if ($liveMode): ?>
                <form method="post" action="/actions/sync.php" class="inline-form">
                    <button type="submit">Jetzt synchronisieren</button>
                </form>
            <?php endif; ?>
        </section>

        <section class="panel connections" id="connections">
            <div class="panel-title"><h3>Verbindungen</h3><span>pro Nutzer separat</span></div>
            <div class="connection-grid">
                <div class="connection-card">
                    <strong>Google</strong>
                    <p>Gmail lesen · Drive lesen · Calendar lesen/schreiben</p>
                    <?php foreach ($googleAccounts as $account): ?>
                        <div class="account-pill"><?= h((string) ($account['email'] ?? $account['account_id'] ?? 'Google')) ?></div>
                    <?php endforeach; ?>
                    <?php if ($googleAccounts !== []): ?>
                        <span class="status-ok">✓ Angemeldet</span>
                    <?php elseif ($factory?->googleConfigured()): ?>
                        <a class="button-link" href="/oauth/google/start.php">Mit Google anmelden</a>
                    <?php else: ?>
                        <a class="button-link disabled" href="#" aria-disabled="true">Mit Google anmelden</a>
                        <span class="muted">Google ist für diese Installation noch nicht freigeschaltet.</span>
                    <?php endif; ?>
                </div>
                <div class="connection-card">
                    <strong>Trello</strong>
                    <p>Boards, Listen und zugewiesene Karten ausschließlich lesen</p>
                    <?php foreach ($trelloAccounts as $account): ?>
                        <?php
                        $accountId = (string) ($account['account_id'] ?? '');
                        $boards = $data['boards'][$accountId] ?? [];
                        $hasSelection = array_key_exists($accountId, $preferences['trello_boards'] ?? []);
                        $selectedBoards = $hasSelection ? ($preferences['trello_boards'][$accountId] ?? []) : null;
                        ?>
                        <div class="account-pill"><?= h((string) ($account['full_name'] ?? $account['username'] ?? 'Trello')) ?></div>
                        <?php if ($boards !== []): ?>
                            <form method="post" action="/actions/boards.php" class="board-form">
                                <input type="hidden" name="account_id" value="<?= h($accountId) ?>">
                                <p class="small-label">Boards für deine Übersicht</p>
                                <?php foreach ($boards as $board): ?>
                                    <?php
                                    $boardId = (string) ($board['id'] ?? '');
                                    $checked = $selectedBoards === null || in_array($boardId, $selectedBoards, true);
                                    ?>
                                    <label class="check-row">
                                        <input type="checkbox" name="boards[]" value="<?= h($boardId) ?>" <?= $checked ? 'checked' : '' ?>>
                                        <span><?= h((string) ($board['name'] ?? 'Board')) ?></span>
                                    </label>
                                <?php endforeach; ?>
                                <button class="secondary" type="submit">Board-Auswahl speichern</button>
                            </form>
                        <?php else: ?>
                            <p class="muted">Nach dem ersten Sync kannst du hier Boards auswählen.</p>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <?php if ($factory?->trelloConfigured()): ?>
                        <a class="button-link" href="/oauth/trello/start.php">Trello verbinden</a>
                    <?php else: ?>
                        <a class="button-link disabled" href="#" aria-disabled="true">Trello verbinden</a>
                        <span class="muted">Trello ist für diese Installation noch nicht freigeschaltet.</span>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section class="grid">
            <div class="panel">
                <div class="panel-title"><h3>Heute</h3><span>Google Calendar</span></div>
                <?php if (($data['today'] ?? []) === []): ?><p class="empty">Keine Termine geladen.</p><?php endif; ?>
                <?php foreach ($data['today'] ?? [] as $item): ?>
                    <article class="timeline-item">
                        <time><?= h((string) $item['time']) ?></time>
                        <div><strong><?= h((string) $item['title']) ?></strong><p><?= h((string) $item['meta']) ?></p></div>
                    </article>
                <?php endforeach; ?>
            </div>

            <div class="panel warning" id="planung">
                <div class="panel-title"><h3>Noch terminieren</h3><span>bewusste Entscheidung nötig</span></div>
                <?php if (($data['unscheduled'] ?? []) === []): ?><p class="empty">Keine offenen Karten ohne Arbeitsblock.</p><?php endif; ?>
                <?php foreach ($data['unscheduled'] ?? [] as $item): ?>
                    <article class="work-item">
                        <div class="source trello">Trello · <?= h((string) ($item['board'] ?? '')) ?></div>
                        <?php if (($item['url'] ?? '') !== ''): ?>
                            <strong><a class="task-title-link" href="<?= h((string) $item['url']) ?>" target="_blank" rel="noopener noreferrer"><?= h((string) $item['title']) ?></a></strong>
                        <?php else: ?>
                            <strong><?= h((string) $item['title']) ?></strong>
                        <?php endif; ?>
                        <p><?= h((string) ($item['body'] ?? '')) ?></p>
                        <?php if (($item['due'] ?? '') !== ''): ?>
                            <p class="due">Trello-Fälligkeit: <?= h((string) $item['due']) ?></p>
                        <?php else: ?>
                            <p class="due critical">Keine Trello-Fälligkeit – bitte bewusst terminieren.</p>
                        <?php endif; ?>

                        <?php if ($googleAccounts !== [] && isset($item['source_id'])): ?>
                            <form method="post" action="/actions/schedule.php" class="schedule-form">
                                <input type="hidden" name="source_id" value="<?= h((string) $item['source_id']) ?>">
                                <input type="hidden" name="title" value="<?= h((string) $item['title']) ?>">
                                <input type="hidden" name="action_token" value="<?= h($actions->issue('schedule', (string) $item['source_id'])) ?>">
                                <label>Datum <input type="date" name="date" required></label>
                                <label>Uhrzeit <input type="time" name="time" required></label>
                                <label>Dauer
                                    <select name="duration">
                                        <option value="30">30 Min.</option>
                                        <option value="60" selected>60 Min.</option>
                                        <option value="90">90 Min.</option>
                                        <option value="120">120 Min.</option>
                                    </select>
                                </label>
                                <label>Kalender
                                    <select name="account_id">
                                        <?php foreach ($googleAccounts as $account): ?>
                                            <option value="<?= h((string) $account['account_id']) ?>"><?= h((string) ($account['email'] ?? $account['account_id'])) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <button type="submit">In Kalender einplanen</button>
                            </form>
                        <?php endif; ?>

                        <div class="task-actions">
                            <form method="post" action="/actions/task-state.php">
                                <input type="hidden" name="source_id" value="<?= h((string) $item['source_id']) ?>">
                                <input type="hidden" name="state" value="done">
                                <input type="hidden" name="action_token" value="<?= h($localActions->issue('task-state', $userId, (string) $item['source_id'], 'done')) ?>">
                                <button class="secondary" type="submit">Intern erledigt</button>
                            </form>
                            <form method="post" action="/actions/task-state.php">
                                <input type="hidden" name="source_id" value="<?= h((string) $item['source_id']) ?>">
                                <input type="hidden" name="state" value="irrelevant">
                                <input type="hidden" name="action_token" value="<?= h($localActions->issue('task-state', $userId, (string) $item['source_id'], 'irrelevant')) ?>">
                                <button class="secondary" type="submit">Irrelevant</button>
                            </form>
                            <form method="post" class="prompt-action">
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
            <div class="panel-title"><h3>E-Mails, die Aufmerksamkeit brauchen</h3><span>read-only · kein Versandweg</span></div>
            <div class="mail-grid">
                <?php if (($data['emails'] ?? []) === []): ?><p class="empty">Keine Antwortkandidaten geladen.</p><?php endif; ?>
                <?php foreach ($data['emails'] ?? [] as $item): ?>
                    <article class="mail-card">
                        <div class="source gmail">Gmail · <?= h((string) ($item['account'] ?? '')) ?></div>
                        <strong><?= h((string) $item['title']) ?></strong>
                        <p>Von <?= h((string) ($item['from'] ?? '')) ?></p>
                        <blockquote><?= h((string) ($item['body'] ?? '')) ?></blockquote>
                        <div class="task-actions">
                            <form method="post" action="/actions/email-state.php">
                                <input type="hidden" name="email_id" value="<?= h((string) $item['id']) ?>">
                                <input type="hidden" name="state" value="hidden">
                                <input type="hidden" name="action_token" value="<?= h($localActions->issue('email-state', $userId, (string) $item['id'], 'hidden')) ?>">
                                <button class="secondary" type="submit">Ausblenden</button>
                            </form>
                            <form method="post">
                                <input type="hidden" name="item_id" value="<?= h((string) $item['id']) ?>">
                                <input type="hidden" name="intent" value="email-reply">
                                <button class="secondary" type="submit">Antwort-Prompt erstellen</button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <?php if ($prompt !== null): ?>
            <section class="panel prompt-panel" id="prompt">
                <div class="panel-title"><h3>Prompt Compiler</h3><span>keine API · kein Versand</span></div>
                <textarea id="compiledPrompt" readonly><?= h((string) $prompt) ?></textarea>
                <button type="button" data-copy-prompt>Prompt kopieren</button>
                <p class="hint">Planyt übergibt den Prompt an niemanden. Er wird nur in die Zwischenablage kopiert.</p>
            </section>
        <?php endif; ?>

        <footer>Trello, Gmail und Drive sind technisch nur lesend vorgesehen. Nur Google Calendar erhält bewusst bestätigte Schreibaktionen.</footer>
    </main>
</div>
<script src="/assets/app.js"></script>
</body>
</html>
