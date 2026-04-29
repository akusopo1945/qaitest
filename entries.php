<?php
declare(strict_types=1);

require_once __DIR__ . '/app/layout.php';
require_once __DIR__ . '/app/storage.php';

function entries_url(array $params = []): string
{
    $query = [];

    foreach ($params as $key => $value) {
        if ($value === null || $value === '') {
            continue;
        }

        $query[(string) $key] = $value;
    }

    $queryString = http_build_query($query);

    return $queryString === '' ? '/entries.php' : '/entries.php?' . $queryString;
}

$updated = ($_GET['updated'] ?? '') === '1';
$deleted = ($_GET['deleted'] ?? '') === '1';
$search = trim((string)($_GET['q'] ?? ''));
$sort = guestbook_normalize_sort((string)($_GET['sort'] ?? 'newest'));
$from = trim((string)($_GET['from'] ?? ''));
$to = trim((string)($_GET['to'] ?? ''));
$perPage = 5;
$currentPage = max(1, (int)($_GET['page'] ?? 1));
$totalEntries = count_guestbook_entries($search, $from, $to);
$totalPages = max(1, (int) ceil($totalEntries / $perPage));
$currentPage = min($currentPage, $totalPages);
$offset = ($currentPage - 1) * $perPage;
$entries = load_guestbook_entries($search, $perPage, $offset, $sort, $from, $to);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string)($_POST['action'] ?? '') === 'delete') {
    $id = trim((string)($_POST['id'] ?? ''));
    $returnSearch = trim((string)($_POST['return_q'] ?? ''));
    $returnSort = guestbook_normalize_sort((string)($_POST['return_sort'] ?? 'newest'));
    $returnFrom = trim((string)($_POST['return_from'] ?? ''));
    $returnTo = trim((string)($_POST['return_to'] ?? ''));
    $returnPage = max(1, (int)($_POST['return_page'] ?? 1));

    if ($id !== '') {
        delete_guestbook_entry($id);
    }

    header('Location: ' . entries_url([
        'deleted' => '1',
        'q' => $returnSearch,
        'sort' => $returnSort,
        'from' => $returnFrom,
        'to' => $returnTo,
        'page' => $returnPage,
    ]));
    exit;
}

render_layout('Entries - Qaitest', 'entries', function () use ($entries, $updated, $deleted, $search, $sort, $from, $to, $currentPage, $totalPages, $totalEntries): void {
    ?>
    <div class="eyebrow">
        <span class="dot" aria-hidden="true"></span>
        Guestbook entries
    </div>

    <h1>Entries</h1>

    <p class="lede">
        Ini halaman list data yang diisi dari form di homepage. Data disimpan lokal ke file JSON atau MySQL, tergantung mode yang aktif.
    </p>

    <form class="form" method="get" action="/entries.php">
        <div class="field">
            <label class="label" for="q">Search</label>
            <input class="input" id="q" name="q" value="<?php echo h($search); ?>" placeholder="Cari nama atau pesan">
        </div>

        <div class="grid">
            <div class="field">
                <label class="label" for="sort">Sort</label>
                <select class="input" id="sort" name="sort">
                    <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest first</option>
                    <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Oldest first</option>
                    <option value="name_asc" <?php echo $sort === 'name_asc' ? 'selected' : ''; ?>>Name A-Z</option>
                    <option value="name_desc" <?php echo $sort === 'name_desc' ? 'selected' : ''; ?>>Name Z-A</option>
                </select>
            </div>

            <div class="field">
                <label class="label" for="from">From date</label>
                <input class="input" type="date" id="from" name="from" value="<?php echo h($from); ?>">
            </div>
        </div>

        <div class="field">
            <label class="label" for="to">To date</label>
            <input class="input" type="date" id="to" name="to" value="<?php echo h($to); ?>">
        </div>

        <div class="button-row">
            <button class="button primary" type="submit">Search</button>
            <a class="button secondary" href="/entries.php">Reset</a>
        </div>
    </form>

    <?php if ($updated): ?>
        <div class="notice" data-testid="updated-notice">
            <span class="tag">Updated</span>
            <div>Entry berhasil diperbarui.</div>
        </div>
    <?php endif; ?>

    <?php if ($deleted): ?>
        <div class="notice warn" data-testid="deleted-notice">
            <span class="tag">Deleted</span>
            <div>Entry berhasil dihapus.</div>
        </div>
    <?php endif; ?>

    <div class="grid">
        <div class="panel">
            <div class="label">Total entries</div>
            <div class="value" data-testid="entries-count"><?php echo $totalEntries; ?> data</div>
        </div>
        <div class="panel">
            <div class="label">Storage mode</div>
            <div class="value"><?php echo guestbook_should_use_mysql() ? 'MySQL' : 'Local JSON file'; ?></div>
        </div>
    </div>

    <div class="stack">
        <div class="panel">
            <div class="entry-head">
                <div>
                    <div class="label">All entries</div>
                    <div class="muted">Sort aktif: <?php echo h(guestbook_sort_label($sort)); ?>.</div>
                </div>
                <a class="button secondary" href="/">Back to home</a>
            </div>

            <?php if ($entries === []) : ?>
                <div class="empty" data-testid="entries-empty">Belum ada data yang disimpan.</div>
            <?php else : ?>
                <table class="table" data-testid="entries-list">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Message</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($entries as $entry) : ?>
                            <tr>
                                <td><?php echo h((string)($entry['name'] ?? 'Guest')); ?></td>
                                <td><?php echo h((string)($entry['message'] ?? '')); ?></td>
                                <td><?php echo h((string)($entry['created_at'] ?? '')); ?></td>
                                <td>
                                    <div class="button-row">
                                        <a class="button secondary" href="/edit.php?id=<?php echo h((string)($entry['id'] ?? '')); ?>&q=<?php echo urlencode($search); ?>&sort=<?php echo urlencode($sort); ?>&from=<?php echo urlencode($from); ?>&to=<?php echo urlencode($to); ?>&page=<?php echo $currentPage; ?>">Edit</a>
                                        <form method="post" action="/entries.php" onsubmit="return confirm('Delete this entry?');" style="display:inline">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo h((string)($entry['id'] ?? '')); ?>">
                                            <input type="hidden" name="return_q" value="<?php echo h($search); ?>">
                                            <input type="hidden" name="return_sort" value="<?php echo h($sort); ?>">
                                            <input type="hidden" name="return_from" value="<?php echo h($from); ?>">
                                            <input type="hidden" name="return_to" value="<?php echo h($to); ?>">
                                            <input type="hidden" name="return_page" value="<?php echo $currentPage; ?>">
                                            <button class="button secondary" type="submit">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <div class="stack">
                <div class="entry-head">
                    <div class="muted">
                        Page <?php echo $currentPage; ?> of <?php echo $totalPages; ?>
                    </div>
                    <div class="button-row">
                        <?php if ($currentPage > 1) : ?>
                            <a class="button secondary" href="<?php echo h(entries_url(['q' => $search, 'sort' => $sort, 'from' => $from, 'to' => $to, 'page' => $currentPage - 1])); ?>">Previous</a>
                        <?php endif; ?>
                        <?php if ($currentPage < $totalPages) : ?>
                            <a class="button secondary" href="<?php echo h(entries_url(['q' => $search, 'sort' => $sort, 'from' => $from, 'to' => $to, 'page' => $currentPage + 1])); ?>">Next</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}, [
    'bodyClass' => 'page-entries',
]);
