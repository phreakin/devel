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

function episodeArtwork(string $label, string $accent): string
{
    $label = esc($label);
    $accent = esc($accent);

    $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 92 92" role="img" aria-label="{$label}">
  <rect width="92" height="92" rx="8" fill="#ffffff"/>
  <rect x="9" y="9" width="74" height="74" rx="6" fill="{$accent}"/>
  <path d="M20 58L35 38l10 14 8-10 19 16H20Z" fill="#f6f2ff" opacity=".88"/>
  <circle cx="66" cy="28" r="8" fill="#f6f2ff" opacity=".94"/>
  <text x="46" y="81" text-anchor="middle" font-family="Segoe UI,Arial,sans-serif" font-size="11" font-weight="700" fill="#070312">{$label}</text>
</svg>
SVG;

    return 'data:image/svg+xml;utf8,' . rawurlencode($svg);
}

function merchArtwork(string $label, string $accent): string
{
    $label = esc($label);
    $accent = esc($accent);

    $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 112 112" role="img" aria-label="{$label}">
  <rect width="112" height="112" rx="12" fill="#ffffff"/>
  <rect x="12" y="12" width="88" height="88" rx="10" fill="{$accent}"/>
  <path d="M30 78V40h14l12 12 12-12h14v38H30Z" fill="#f6f2ff" opacity=".92"/>
  <path d="M36 50h40" stroke="#070312" stroke-width="4" stroke-linecap="round" opacity=".45"/>
  <path d="M36 61h28" stroke="#070312" stroke-width="4" stroke-linecap="round" opacity=".45"/>
  <text x="56" y="99" text-anchor="middle" font-family="Segoe UI,Arial,sans-serif" font-size="11" font-weight="700" fill="#070312">{$label}</text>
</svg>
SVG;

    return 'data:image/svg+xml;utf8,' . rawurlencode($svg);
}

function renderDashboard(array $state): string
{
    $episodes = [
        [
            'number' => 'Episode 01',
            'status' => 'LIVE',
            'statusClass' => 'live',
            'title' => 'Signal Intake',
            'release' => 'Broadcast date: April 22, 2026',
            'style' => 'Format: live briefing',
            'limited' => 'Live now on the main channel.',
            'items' => [
                'Opening sequence and orientation',
                'Current system state and route map',
                'Immediate operator actions',
            ],
            'cta' => 'Open live feed',
            'art' => episodeArtwork('EP 01', '#17306e'),
        ],
        [
            'number' => 'Episode 02',
            'status' => 'COMING SOON',
            'statusClass' => 'coming-soon',
            'title' => 'Night Transmission',
            'release' => 'Broadcast date: April 29, 2026',
            'style' => 'Format: serialized update',
            'limited' => 'Limited preview access only.',
            'items' => [
                'New archive segment',
                'Expanded route coverage',
                'Fresh utility panel',
            ],
            'cta' => 'Reserve preview',
            'art' => episodeArtwork('EP 02', '#6a1a66'),
        ],
        [
            'number' => 'Episode 03',
            'status' => 'SOLD OUT',
            'statusClass' => 'sold-out',
            'title' => 'Offline Vault',
            'release' => 'Broadcast date: May 6, 2026',
            'style' => 'Format: collector drop',
            'limited' => 'Archive bundle exhausted.',
            'items' => [
                'Recovered assets and notes',
                'Behind-the-scenes log',
                'Restricted extras bundle',
            ],
            'cta' => 'Join waitlist',
            'art' => episodeArtwork('EP 03', '#6e1830'),
        ],
    ];

    $merch = [
        [
            'name' => 'Signal Archive Tee',
            'tag' => 'Core drop',
            'price' => '$34',
            'description' => 'Heavyweight black tee with cyan grid print and the archive crest.',
            'cta' => 'Shop tee',
            'accent' => '#17306e',
        ],
        [
            'name' => 'Night Transmission Hoodie',
            'tag' => 'Limited run',
            'price' => '$68',
            'description' => 'Oversized hoodie with magenta sleeve marks and back-panel art.',
            'cta' => 'Shop hoodie',
            'accent' => '#6a1a66',
        ],
        [
            'name' => 'Broadcast Mug',
            'tag' => 'Desk gear',
            'price' => '$18',
            'description' => 'Gloss mug for late-night edits, planning calls, and episode notes.',
            'cta' => 'Shop mug',
            'accent' => '#0e5f6f',
        ],
        [
            'name' => 'Sticker Pack',
            'tag' => 'Add-on',
            'price' => '$12',
            'description' => 'Die-cut decals built from the episode badges, crest, and grid line art.',
            'cta' => 'Shop stickers',
            'accent' => '#6e1830',
        ],
    ];

    ob_start();
    ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="dark">
    <title><?= esc($state['appName']) ?> Signal Archive</title>
    <link rel="stylesheet" href="/assets/app.css">
</head>
<body>
    <div class="grid-horizon" aria-hidden="true"></div>

    <header class="site-header">
        <div class="crest" aria-hidden="true"><span><?= esc(strtoupper(substr($state['appName'], 0, 2))) ?></span></div>
        <nav aria-label="Primary">
            <ul class="nav-list">
                <li><a href="#overview">Overview</a></li>
                <li><a href="#episodes">Episodes</a></li>
                <li><a href="#merch">Merch</a></li>
                <li><a href="#signal">Signal</a></li>
            </ul>
        </nav>
        <a class="cta-button" href="/api/health">Validate API</a>
    </header>

    <main>
        <section class="intro">
            <h1><?= esc($state['appName']) ?> signal archive</h1>
            <p>Neon broadcast layout with live, coming soon, and sold-out episode states wired into the homepage.</p>
        </section>

        <section class="overview" id="overview">
            <h2>Overview</h2>
            <p>
                This site is serving the requested horizon-grid theme with episode cards, compact utility panels,
                and the same dark cyan-magenta palette from the document.
            </p>
        </section>

        <section id="episodes">
            <h2>Episodes</h2>
            <div class="episodes">
                <?php foreach ($episodes as $episode): ?>
                    <article class="episode-card <?= esc($episode['statusClass']) ?>">
                        <div class="episode-art"><?= esc(substr($episode['number'], -2)) ?></div>
                        <div class="card-head">
                            <h3 class="episode-number"><?= esc($episode['number']) ?></h3>
                            <span class="status-badge"><?= esc($episode['status']) ?></span>
                        </div>
                        <h4><?= esc($episode['title']) ?></h4>
                        <p class="release"><?= esc($episode['release']) ?></p>
                        <p class="style-tag"><?= esc($episode['style']) ?></p>
                        <p class="limited"><?= esc($episode['limited']) ?></p>
                        <ul class="items">
                            <?php foreach ($episode['items'] as $item): ?>
                                <li><?= esc($item) ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <div class="card-footer">
                            <a class="card-cta" href="#signal"><?= esc($episode['cta']) ?></a>
                            <img src="<?= esc($episode['art']) ?>" alt="<?= esc($episode['title']) ?> artwork">
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="merch-section" id="merch">
            <div class="section-head">
                <div>
                    <p class="section-kicker">Merch</p>
                    <h2>Podcast gear</h2>
                </div>
                <a class="merch-partner-link" href="#signal">Partner store ready</a>
            </div>
            <p class="merch-intro">
                This is a static merch block for the podcast brand. If you want live stock and checkout, we can wire this
                to a merch partner API next.
            </p>
            <div class="merch-grid">
                <?php foreach ($merch as $item): ?>
                    <article class="merch-card">
                        <div class="merch-art">
                            <img src="<?= esc(merchArtwork($item['name'], $item['accent'])) ?>" alt="<?= esc($item['name']) ?> artwork">
                        </div>
                        <div class="merch-copy">
                            <div class="merch-meta">
                                <span><?= esc($item['tag']) ?></span>
                                <strong><?= esc($item['price']) ?></strong>
                            </div>
                            <h3><?= esc($item['name']) ?></h3>
                            <p><?= esc($item['description']) ?></p>
                            <a class="card-cta" href="#signal"><?= esc($item['cta']) ?></a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="utility-section" id="signal">
            <h2>Signal</h2>
            <p>
                API health is available at <code>/api/health</code>. Database state is <?= esc($state['dbStatus']) ?>,
                JWT is <?= esc($state['jwtStatus']) ?>, and AI is <?= esc($state['aiStatus']) ?>.
            </p>
        </section>

        <section class="utility-section">
            <h2>Runbook</h2>
            <p>
                Start the app with <code>php -S 127.0.0.1:8000 -t public public/index.php</code> and validate the
                API with <code>curl http://127.0.0.1:8000/api/health</code>.
            </p>
        </section>
    </div>

    <footer class="site-footer">
        <div>Broadcast status: <?= esc(strtoupper($state['environment'])) ?></div>
        <div class="tour-badge">Route count <?= esc((string)$state['routeCount']) ?></div>
    </footer>
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
