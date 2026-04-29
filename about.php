<?php
declare(strict_types=1);

require_once __DIR__ . '/app/layout.php';

$serverName = $_SERVER['SERVER_NAME'] ?? 'unknown';

render_layout('About - Qaitest', 'about', function () use ($serverName): void {
    ?>
    <div class="eyebrow">
        <span class="dot" aria-hidden="true"></span>
        Local PHP playground
    </div>

    <h1>About Qaitest</h1>

    <p class="lede">
        Ini adalah local PHP playground yang bisa diakses lewat <strong>qaitest.test</strong>.
        Sekarang isinya sudah ada landing page, guestbook sederhana, halaman entries, dan footer credit yang konsisten.
    </p>

    <div class="grid">
        <div class="panel">
            <div class="label">Server name</div>
            <div class="value"><?php echo h($serverName); ?></div>
        </div>
        <div class="panel">
            <div class="label">What it does</div>
            <div class="value">Ngecek stack lokal, simpan data sederhana ke JSON, dan jadi target smoke test Playwright.</div>
        </div>
    </div>

    <div class="stack">
        <div class="panel">
            <div class="label">Current pages</div>
            <div class="value">
                Home, About, dan Entries. Semua route ini tetap ringan, reusable, dan gampang dikembangkan.
            </div>
        </div>
    </div>
    <?php
}, [
    'bodyClass' => 'page-about',
]);
