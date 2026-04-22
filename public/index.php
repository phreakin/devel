<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
}

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

function esc(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function buildDashboardState(): array
{
    $state = [
        'appName' => $_ENV['APP_NAME'] ?? 'Devel',
        'environment' => $_ENV['APP_ENV'] ?? 'production',
        'dbStatus' => 'offline',
        'dbMessage' => 'Database unavailable',
        'userCount' => null,
        'conversationCount' => null,
        'messageCount' => null,
        'latestConversations' => [],
        'apiStatus' => 'ready',
        'jwtStatus' => !empty($_ENV['JWT_SECRET']) ? 'configured' : 'missing',
        'aiStatus' => !empty($_ENV['OPENAI_API_KEY']) ? 'configured' : 'missing',
        'serverTime' => date('c'),
        'routeCount' => 7,
    ];

    try {
        $entityManager = require __DIR__ . '/../config/doctrine.php';

        $userRepo = $entityManager->getRepository(\App\Entity\User::class);
        $conversationRepo = $entityManager->getRepository(\App\Entity\Conversation::class);
        $messageRepo = $entityManager->getRepository(\App\Entity\Message::class);

        $users = $userRepo->findAll();
        $conversations = $conversationRepo->findAll();
        $messages = $messageRepo->findAll();

        usort($conversations, static fn($left, $right) => $right->getUpdatedAt() <=> $left->getUpdatedAt());

        $state['dbStatus'] = 'online';
        $state['dbMessage'] = 'Database connected';
        $state['userCount'] = count($users);
        $state['conversationCount'] = count($conversations);
        $state['messageCount'] = count($messages);
        $state['latestConversations'] = array_map(
            static fn(\App\Entity\Conversation $conversation) => [
                'id' => $conversation->getId(),
                'title' => $conversation->getTitle(),
                'model' => $conversation->getAiModel(),
                'messageCount' => $conversation->getMessageCount(),
                'updatedAt' => $conversation->getUpdatedAt()->format('M j, Y g:i A'),
            ],
            array_slice($conversations, 0, 5)
        );
    } catch (\Throwable $e) {
        $state['dbMessage'] = 'Database offline';
        $state['dbError'] = $e->getMessage();
    }

    return $state;
}

function renderDashboard(array $state): string
{
    ob_start();
    ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="dark">
    <title><?= esc($state['appName']) ?> Control Center</title>
    <link rel="stylesheet" href="/assets/app.css">
</head>
<body>
    <div class="page-shell">
        <div class="ambient ambient-a"></div>
        <div class="ambient ambient-b"></div>

        <header class="topbar">
            <div class="brand">
                <div class="brand-mark">D</div>
                <div>
                    <div class="brand-kicker"><?= esc($state['appName']) ?></div>
                    <div class="brand-title">Control Center</div>
                </div>
            </div>

            <div class="status-strip">
                <span class="status-chip status-chip-good">API ready</span>
                <span class="status-chip"><?= esc(strtoupper($state['environment'])) ?></span>
                <span class="status-chip"><?= esc($state['dbStatus'] === 'online' ? 'DB online' : 'DB offline') ?></span>
            </div>
        </header>

        <main class="dashboard">
            <section class="hero">
                <div class="hero-copy">
                    <p class="eyebrow">Operational dashboard</p>
                    <h1>Authentication, conversations, and AI workflows in one view.</h1>
                    <p class="lede">
                        This front page now acts as a working dashboard instead of a plain API landing page.
                        It shows live service state, counts, and the route surface the app exposes.
                    </p>
                    <div class="hero-actions">
                        <a class="button button-primary" href="/api/health">Check API health</a>
                        <a class="button button-secondary" href="#routes">View routes</a>
                    </div>
                </div>

                <aside class="hero-panel">
                    <div class="panel-label">Current state</div>
                    <div class="state-grid">
                        <div>
                            <span>Database</span>
                            <strong><?= esc(ucfirst($state['dbStatus'])) ?></strong>
                        </div>
                        <div>
                            <span>JWT</span>
                            <strong><?= esc(ucfirst($state['jwtStatus'])) ?></strong>
                        </div>
                        <div>
                            <span>AI</span>
                            <strong><?= esc(ucfirst($state['aiStatus'])) ?></strong>
                        </div>
                        <div>
                            <span>Server time</span>
                            <strong><?= esc(date('M j, g:i A')) ?></strong>
                        </div>
                    </div>
                </aside>
            </section>

            <section class="metrics" aria-label="Key metrics">
                <article class="metric">
                    <span>Users</span>
                    <strong><?= esc((string)($state['userCount'] ?? '—')) ?></strong>
                    <p>Registered accounts in the local dataset.</p>
                </article>
                <article class="metric">
                    <span>Conversations</span>
                    <strong><?= esc((string)($state['conversationCount'] ?? '—')) ?></strong>
                    <p>Saved chat threads available to the workspace.</p>
                </article>
                <article class="metric">
                    <span>Messages</span>
                    <strong><?= esc((string)($state['messageCount'] ?? '—')) ?></strong>
                    <p>Persisted message count across conversations.</p>
                </article>
                <article class="metric">
                    <span>Routes</span>
                    <strong><?= esc((string)$state['routeCount']) ?></strong>
                    <p>Public and API endpoints wired in the app.</p>
                </article>
            </section>

            <section class="content-grid">
                <section class="panel panel-wide" id="routes">
                    <div class="panel-head">
                        <div>
                            <p class="panel-kicker">API surface</p>
                            <h2>Live routes</h2>
                        </div>
                        <span class="panel-note">JSON under <code>/api</code></span>
                    </div>

                    <div class="route-list">
                        <div class="route-row"><span class="method method-get">GET</span><code>/api/health</code><span>Service health</span></div>
                        <div class="route-row"><span class="method method-post">POST</span><code>/api/auth/register</code><span>Register user</span></div>
                        <div class="route-row"><span class="method method-post">POST</span><code>/api/auth/login</code><span>Issue JWT</span></div>
                        <div class="route-row"><span class="method method-post">POST</span><code>/api/conversations</code><span>Create thread</span></div>
                        <div class="route-row"><span class="method method-get">GET</span><code>/api/conversations</code><span>List threads</span></div>
                        <div class="route-row"><span class="method method-post">POST</span><code>/api/conversations/{conversationId}/messages</code><span>Add message</span></div>
                        <div class="route-row"><span class="method method-get">GET</span><code>/api/conversations/{conversationId}/messages</code><span>Read history</span></div>
                    </div>
                </section>

                <section class="panel">
                    <div class="panel-head">
                        <div>
                            <p class="panel-kicker">Latest threads</p>
                            <h2>Recent conversations</h2>
                        </div>
                        <span class="panel-note">Updated latest first</span>
                    </div>

                    <?php if (!empty($state['latestConversations'])): ?>
                        <div class="conversation-list">
                            <?php foreach ($state['latestConversations'] as $conversation): ?>
                                <article class="conversation-row">
                                    <div>
                                        <strong><?= esc($conversation['title']) ?></strong>
                                        <span><?= esc($conversation['model']) ?></span>
                                    </div>
                                    <div class="conversation-meta">
                                        <span><?= esc((string)$conversation['messageCount']) ?> messages</span>
                                        <span><?= esc($conversation['updatedAt']) ?></span>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <strong>No conversations yet.</strong>
                            <p>Register a user, log in, and create a thread to populate this panel.</p>
                        </div>
                    <?php endif; ?>
                </section>
            </section>

            <section class="content-grid content-grid-bottom">
                <section class="panel">
                    <div class="panel-head">
                        <div>
                            <p class="panel-kicker">Runbook</p>
                            <h2>Local workflow</h2>
                        </div>
                    </div>

                    <div class="runbook">
                        <div>
                            <span>Start schema</span>
                            <code>php setup_db.php</code>
                        </div>
                        <div>
                            <span>Run tests</span>
                            <code>vendor/bin/phpunit</code>
                        </div>
                        <div>
                            <span>Serve app</span>
                            <code>php -S 127.0.0.1:8000 -t public public/index.php</code>
                        </div>
                    </div>
                </section>

                <section class="panel">
                    <div class="panel-head">
                        <div>
                            <p class="panel-kicker">Status</p>
                            <h2>Environment flags</h2>
                        </div>
                    </div>

                    <div class="flag-list">
                        <div><span>APP_ENV</span><strong><?= esc($state['environment']) ?></strong></div>
                        <div><span>JWT_SECRET</span><strong><?= esc($state['jwtStatus']) ?></strong></div>
                        <div><span>OPENAI_API_KEY</span><strong><?= esc($state['aiStatus']) ?></strong></div>
                        <div><span>Database</span><strong><?= esc($state['dbStatus']) ?></strong></div>
                    </div>
                </section>
            </section>
        </main>
    </div>
</body>
</html>
    <?php

    return (string) ob_get_clean();
}

if ($requestPath === '/' || $requestPath === '/index.php') {
    header('Content-Type: text/html; charset=UTF-8');
    echo renderDashboard(buildDashboardState());
    exit;
}

if (!str_starts_with($requestPath, '/api')) {
    http_response_code(404);
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!doctype html><html><head><meta charset="utf-8"><title>Not Found</title></head><body><h1>Not Found</h1></body></html>';
    exit;
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$entityManager = require __DIR__ . '/../config/doctrine.php';
$openaiApiKey = $_ENV['OPENAI_API_KEY'] ?? null;
$jwtSecret = $_ENV['JWT_SECRET'] ?? null;

$request = \App\Http\Request::fromGlobal();
$api = new \App\ApiApplication($entityManager, $openaiApiKey, $jwtSecret);

try {
    $response = $api->handleRequest($request);
    http_response_code($response->getStatusCode());
    echo $response->toJson();
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error',
        'error' => $_ENV['APP_DEBUG'] === 'true' ? $e->getMessage() : null,
    ]);
}
