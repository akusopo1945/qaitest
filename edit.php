<?php
declare(strict_types=1);

require_once __DIR__ . '/app/layout.php';
require_once __DIR__ . '/app/storage.php';

function edit_return_entries_url(array $params = []): string
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

$id = trim((string)($_GET['id'] ?? $_POST['id'] ?? ''));
$returnSearch = trim((string)($_GET['q'] ?? $_POST['return_q'] ?? ''));
$returnSort = guestbook_normalize_sort((string)($_GET['sort'] ?? $_POST['return_sort'] ?? 'newest'));
$returnFrom = trim((string)($_GET['from'] ?? $_POST['return_from'] ?? ''));
$returnTo = trim((string)($_GET['to'] ?? $_POST['return_to'] ?? ''));
$returnPage = max(1, (int)($_GET['page'] ?? $_POST['return_page'] ?? 1));
$entry = $id !== '' ? get_guestbook_entry($id) : null;

if ($entry === null) {
    render_layout('Edit Entry - Qaitest', 'entries', function () use ($id, $returnSearch, $returnSort, $returnFrom, $returnTo, $returnPage): void {
        ?>
        <div class="eyebrow">
            <span class="dot" aria-hidden="true"></span>
            Edit guestbook
        </div>

        <h1>Edit Entry</h1>

        <p class="lede">Entry yang kamu cari tidak ditemukan. Mungkin sudah dihapus atau id-nya salah.</p>

        <div class="stack">
            <div class="notice warn">
                <span class="tag">404</span>
                <div>Entry tidak tersedia untuk id: <code><?php echo h($id); ?></code></div>
            </div>

            <a class="button secondary" href="<?php echo h(edit_return_entries_url(['q' => $returnSearch, 'sort' => $returnSort, 'from' => $returnFrom, 'to' => $returnTo, 'page' => $returnPage])); ?>">Back to entries</a>
        </div>
        <?php
    }, [
        'bodyClass' => 'page-edit',
    ]);

    return;
}

$serverName = $_SERVER['SERVER_NAME'] ?? 'unknown';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string)($_POST['name'] ?? ''));
    $message = trim((string)($_POST['message'] ?? ''));

    $name = $name !== '' ? $name : 'Guest';
    $message = $message !== '' ? $message : 'Mampir dari homepage Qaitest.';

    if (update_guestbook_entry($id, $name, $message)) {
        header('Location: ' . edit_return_entries_url([
            'updated' => '1',
            'q' => $returnSearch,
            'sort' => $returnSort,
            'from' => $returnFrom,
            'to' => $returnTo,
            'page' => $returnPage,
        ]));
        exit;
    }
}

render_layout('Edit Entry - Qaitest', 'entries', function () use ($id, $entry, $serverName, $returnSearch, $returnSort, $returnFrom, $returnTo, $returnPage): void {
    ?>
    <div class="eyebrow">
        <span class="dot" aria-hidden="true"></span>
        Edit guestbook
    </div>

    <h1>Edit Entry</h1>

    <p class="lede">
        Ubah data yang sudah tersimpan di guestbook lokal tanpa perlu menghapus dulu.
    </p>

    <div class="grid">
        <div class="panel">
            <div class="label">Entry ID</div>
            <div class="value"><?php echo h($id); ?></div>
        </div>
        <div class="panel">
            <div class="label">Server name</div>
            <div class="value"><?php echo h($serverName); ?></div>
        </div>
    </div>

    <form class="form" method="post" action="/edit.php">
        <input type="hidden" name="id" value="<?php echo h($id); ?>">
        <input type="hidden" name="return_q" value="<?php echo h($returnSearch); ?>">
        <input type="hidden" name="return_page" value="<?php echo $returnPage; ?>">

        <div class="field">
            <label class="label" for="name">Nama pengunjung</label>
            <input class="input" id="name" name="name" value="<?php echo h((string)($entry['name'] ?? 'Guest')); ?>">
        </div>

        <div class="field">
            <label class="label" for="message">Pesan</label>
            <textarea class="textarea" id="message" name="message"><?php echo h((string)($entry['message'] ?? '')); ?></textarea>
        </div>

        <input type="hidden" name="return_sort" value="<?php echo h($returnSort); ?>">
        <input type="hidden" name="return_from" value="<?php echo h($returnFrom); ?>">
        <input type="hidden" name="return_to" value="<?php echo h($returnTo); ?>">

        <div class="button-row">
            <button class="button primary" type="submit">Update entry</button>
            <a class="button secondary" href="<?php echo h(edit_return_entries_url(['q' => $returnSearch, 'sort' => $returnSort, 'from' => $returnFrom, 'to' => $returnTo, 'page' => $returnPage])); ?>">Back to entries</a>
        </div>
    </form>
    <?php
}, [
    'bodyClass' => 'page-edit',
]);
