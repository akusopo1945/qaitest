<?php
declare(strict_types=1);

require_once __DIR__ . '/app/layout.php';
require_once __DIR__ . '/app/storage.php';

$serverName = $_SERVER['SERVER_NAME'] ?? 'unknown';
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$saved = ($_GET['saved'] ?? '') === '1';
$visitorName = trim((string)($_GET['name'] ?? ''));
$visitorName = $visitorName !== '' ? $visitorName : 'Guest';
$entryCount = count_guestbook_entries();
$recentEntries = load_guestbook_entries(null, 3, 0);

$demoPrompt = 'cek user bisa submit guestbook lalu entry muncul di halaman entries';
$demoPlan = [
    [
        'step' => 'Prompt',
        'title' => 'Natural language intent',
        'body' => 'User menulis test pakai bahasa manusia. AI menangkap tujuan tanpa harus mikir selector dulu.',
    ],
    [
        'step' => 'Plan',
        'title' => 'Structured execution plan',
        'body' => 'AI mengubah intent jadi JSON plan berisi navigate, fill, click, assertion, dan cleanup.',
    ],
    [
        'step' => 'Run',
        'title' => 'Playwright execution',
        'body' => 'Playwright menjalankan langkah dengan deterministik. Error, screenshot, dan trace tetap eksplisit.',
    ],
    [
        'step' => 'Summary',
        'title' => 'Readable AI summary',
        'body' => 'Hasil teknis diringkas jadi insight yang gampang dibaca tim produk atau developer.',
    ],
];
$demoRun = [
    'status' => 'Passed',
    'plan_id' => 'guestbook-happy',
    'model' => 'gpt-5.5',
    'assertions' => '2/2',
    'cleanup' => '1/1',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedName = trim((string)($_POST['name'] ?? ''));
    $postedMessage = trim((string)($_POST['message'] ?? ''));

    $entryName = $postedName !== '' ? $postedName : 'Guest';
    $entryMessage = $postedMessage !== '' ? $postedMessage : 'Mampir dari homepage Qaitest.';

    append_guestbook_entry(create_guestbook_entry($entryName, $entryMessage, $serverName, $requestUri));

    header('Location: /?saved=1&name=' . rawurlencode($entryName));
    exit;
}

render_layout('Qaitest', 'home', function () use ($serverName, $requestUri, $visitorName, $saved, $recentEntries, $entryCount, $demoPrompt, $demoPlan, $demoRun): void {
    ?>
    <div class="hero">
        <div class="hero-copy">
            <div class="eyebrow">
                <span class="dot" aria-hidden="true"></span>
                Local QA product demo
            </div>

            <div class="hero-title">
                <span class="eyebrow-pill">AI planning + Playwright execution</span>
                <h1 class="hero-headline" id="page-title">Qaitest</h1>
            </div>

            <p class="lede">
                Qaitest adalah playground QA lokal yang bisa dibuka dari browser lewat host
                <strong>qaitest.test</strong>. Di sini ada guestbook kecil, dashboard QA, dan preview
                alur kerja AI yang disusun supaya mudah dipahami.
            </p>

            <div class="hero-actions">
                <a class="button primary" href="/qa.php">Buka QA Dashboard</a>
                <a class="button secondary" href="/entries.php">Lihat entries</a>
                <a class="button secondary" href="/about.php">About page</a>
            </div>

            <div class="metric-strip">
                <div class="metric">
                    <div class="metric-label">Status</div>
                    <div class="metric-value" data-testid="status-chip">Berhasil! Web Server berjalan.</div>
                </div>
                <div class="metric">
                    <div class="metric-label">Mode</div>
                    <div class="metric-value">AI-planned QA</div>
                </div>
                <div class="metric">
                    <div class="metric-label">Storage</div>
                    <div class="metric-value"><?php echo guestbook_should_use_mysql() ? 'MySQL' : 'JSON fallback'; ?></div>
                </div>
            </div>
        </div>

        <div class="hero-panel">
            <div class="hero-visual">
                <div class="demo-shell">
                    <div class="demo-title">
                        <div>
                            <div class="label">Sample QA run</div>
                            <div class="muted">Preview ringkas alur kerja yang dijalankan dari prompt ke hasil.</div>
                        </div>
                        <span class="tag"><?php echo h($demoRun['status']); ?></span>
                    </div>

                    <div class="demo-card" data-testid="demo-prompt">
                        <div class="label">Prompt</div>
                        <div class="value"><?php echo h($demoPrompt); ?></div>
                    </div>

                    <div class="demo-flow" data-testid="demo-flow">
                        <?php foreach ($demoPlan as $index => $item): ?>
                            <div class="demo-step">
                                <div class="entry-head">
                                    <strong><?php echo h($item['title']); ?></strong>
                                    <span class="tag"><?php echo h($item['step']); ?></span>
                                </div>
                                <div class="muted"><?php echo h($item['body']); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="metric-strip">
                        <div class="metric">
                            <div class="metric-label">Plan ID</div>
                            <div class="metric-value"><?php echo h($demoRun['plan_id']); ?></div>
                        </div>
                        <div class="metric">
                            <div class="metric-label">Model</div>
                            <div class="metric-value"><?php echo h($demoRun['model']); ?></div>
                        </div>
                        <div class="metric">
                            <div class="metric-label">Assertions</div>
                            <div class="metric-value"><?php echo h($demoRun['assertions']); ?></div>
                        </div>
                    </div>

                    <div class="demo-card" data-testid="demo-summary">
                        <div class="label">AI summary</div>
                        <div class="value">
                            Flow berhasil. Entry guestbook tampil di halaman entries, seluruh assertion lolos, dan cleanup berjalan normal.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($saved): ?>
        <div class="notice" data-testid="saved-notice">
            <span class="tag">Saved</span>
            <div>Data kamu sudah disimpan ke guestbook lokal.</div>
        </div>
    <?php endif; ?>

    <div class="stack">
        <div class="panel">
            <div class="entry-head">
                <div>
                    <div class="label">Quick metrics</div>
                    <div class="muted">State runtime yang penting buat QA dan debug.</div>
                </div>
            </div>

            <div class="grid">
                <div class="panel">
                    <div class="label">Server name</div>
                    <div class="value" data-testid="server-name"><?php echo h($serverName); ?></div>
                </div>

                <div class="panel">
                    <div class="label">Request URI</div>
                    <div class="value" data-testid="request-uri"><?php echo h($requestUri); ?></div>
                </div>

                <div class="panel">
                    <div class="label">Greeting</div>
                    <div class="value" data-testid="visitor-greeting">Halo, <?php echo h($visitorName); ?>.</div>
                </div>

                <div class="panel">
                    <div class="label">Guestbook entries</div>
                    <div class="value" data-testid="entry-count"><?php echo $entryCount; ?> data tersimpan</div>
                </div>
            </div>
        </div>

        <div class="panel">
            <div class="entry-head">
                <div>
                    <div class="label">Guestbook input</div>
                    <div class="muted">Form sederhana buat nyimpen pengunjung dan pesan singkat.</div>
                </div>
                <a class="button secondary" href="/entries.php">Buka semua</a>
            </div>

            <form class="form" method="post" action="/">
                <div class="field">
                    <label class="label" for="name">Nama pengunjung</label>
                    <input class="input" id="name" name="name" value="<?php echo h($visitorName); ?>" placeholder="Tulis nama kamu">
                </div>

                <div class="field">
                    <label class="label" for="message">Pesan</label>
                    <textarea class="textarea" id="message" name="message" placeholder="Tulis pesan singkat buat guestbook">Mampir dari homepage Qaitest.</textarea>
                </div>

                <div class="button-row">
                    <button class="button primary" type="submit">Simpan ke guestbook</button>
                    <a class="button secondary" href="/qa.php">Buka QA dashboard</a>
                </div>
            </form>
        </div>

        <div class="panel">
            <div class="entry-head">
                <div>
                    <div class="label">Recent entries</div>
                    <div class="muted">Tiga data terakhir dari guestbook lokal.</div>
                </div>
                <a class="button secondary" href="/entries.php">Buka semua</a>
            </div>

            <div class="list" data-testid="recent-entries">
                <?php if ($recentEntries === []) : ?>
                    <div class="empty">Belum ada data. Coba kirim pesan pertama lewat form di atas.</div>
                <?php else : ?>
                    <?php foreach ($recentEntries as $entry) : ?>
                        <article class="entry">
                            <div class="entry-head">
                                <div class="entry-name"><?php echo h((string)($entry['name'] ?? 'Guest')); ?></div>
                                <div class="entry-meta"><?php echo h((string)($entry['created_at'] ?? '')); ?></div>
                            </div>
                            <div class="entry-message"><?php echo h((string)($entry['message'] ?? '')); ?></div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
}, [
    'bodyClass' => 'page-home',
    'shellClass' => 'shell shell-home',
    'cardClass' => 'card card-home',
]);
