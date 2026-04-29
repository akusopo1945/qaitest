<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

function guestbook_storage_path(): string
{
    return __DIR__ . '/../storage/entries.json';
}

function guestbook_mysql_table_name(): string
{
    $config = guestbook_mysql_config();
    return preg_replace('/[^a-zA-Z0-9_]/', '', $config['table']);
}

function guestbook_allowed_sorts(): array
{
    return [
        'newest',
        'oldest',
        'name_asc',
        'name_desc',
    ];
}

function guestbook_normalize_sort(string $sort): string
{
    $sort = strtolower(trim($sort));
    return in_array($sort, guestbook_allowed_sorts(), true) ? $sort : 'newest';
}

function guestbook_sort_label(string $sort): string
{
    return match (guestbook_normalize_sort($sort)) {
        'oldest' => 'Oldest first',
        'name_asc' => 'Name A-Z',
        'name_desc' => 'Name Z-A',
        default => 'Newest first',
    };
}

function guestbook_parse_query_date(?string $value): ?DateTimeImmutable
{
    $value = trim((string) $value);

    if ($value === '') {
        return null;
    }

    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value, new DateTimeZone('UTC'));
    if ($date instanceof DateTimeImmutable) {
        return $date;
    }

    return null;
}

function guestbook_parse_created_at(?string $value): ?DateTimeImmutable
{
    $value = trim((string) $value);

    if ($value === '') {
        return null;
    }

    try {
        return (new DateTimeImmutable($value))->setTimezone(new DateTimeZone('UTC'));
    } catch (Throwable $e) {
        return null;
    }
}

function guestbook_format_created_at(DateTimeImmutable $dateTime): string
{
    return $dateTime->setTimezone(new DateTimeZone('UTC'))->format(DATE_ATOM);
}

function guestbook_format_mysql_datetime(DateTimeImmutable $dateTime): string
{
    return $dateTime->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
}

function guestbook_entry_matches_date_window(array $entry, ?DateTimeImmutable $from, ?DateTimeImmutable $toExclusive): bool
{
    $createdAt = guestbook_parse_created_at((string)($entry['created_at'] ?? ''));

    if ($createdAt === null) {
        return true;
    }

    if ($from instanceof DateTimeImmutable && $createdAt < $from) {
        return false;
    }

    if ($toExclusive instanceof DateTimeImmutable && $createdAt >= $toExclusive) {
        return false;
    }

    return true;
}

function guestbook_sort_entries(array $entries, string $sort): array
{
    $sort = guestbook_normalize_sort($sort);

    usort($entries, static function (array $left, array $right) use ($sort): int {
        $leftDate = guestbook_parse_created_at((string)($left['created_at'] ?? ''));
        $rightDate = guestbook_parse_created_at((string)($right['created_at'] ?? ''));
        $leftName = strtolower(trim((string)($left['name'] ?? '')));
        $rightName = strtolower(trim((string)($right['name'] ?? '')));
        $leftId = (string)($left['id'] ?? '');
        $rightId = (string)($right['id'] ?? '');

        return match ($sort) {
            'oldest' => ($leftDate <=> $rightDate) ?: ($leftId <=> $rightId),
            'name_asc' => ($leftName <=> $rightName) ?: (($rightDate <=> $leftDate) ?: ($leftId <=> $rightId)),
            'name_desc' => ($rightName <=> $leftName) ?: (($rightDate <=> $leftDate) ?: ($leftId <=> $rightId)),
            default => ($rightDate <=> $leftDate) ?: ($rightId <=> $leftId),
        };
    });

    return array_values($entries);
}

function guestbook_filter_entries(array $entries, ?DateTimeImmutable $from, ?DateTimeImmutable $toExclusive): array
{
    return array_values(array_filter($entries, static function (array $entry) use ($from, $toExclusive): bool {
        return guestbook_entry_matches_date_window($entry, $from, $toExclusive);
    }));
}

function guestbook_mysql_date_window(?string $from, ?string $to): array
{
    $fromDate = guestbook_parse_query_date($from);
    $toDate = guestbook_parse_query_date($to);

    return [
        $fromDate,
        $toDate instanceof DateTimeImmutable ? $toDate->modify('+1 day') : null,
    ];
}

function guestbook_mysql_order_clause(string $sort): string
{
    return match (guestbook_normalize_sort($sort)) {
        'oldest' => 'created_at ASC, id ASC',
        'name_asc' => 'name ASC, created_at DESC, id DESC',
        'name_desc' => 'name DESC, created_at DESC, id DESC',
        default => 'created_at DESC, id DESC',
    };
}

function guestbook_mysql_build_filters(?string $search, ?string $from, ?string $to, array &$params): string
{
    $clauses = [];
    $search = trim((string) $search);

    if ($search !== '') {
        $clauses[] = '(name LIKE :search OR message LIKE :search)';
        $params[':search'] = '%' . $search . '%';
    }

    [$fromDate, $toExclusive] = guestbook_mysql_date_window($from, $to);

    if ($fromDate instanceof DateTimeImmutable) {
        $clauses[] = 'created_at >= :from_date';
        $params[':from_date'] = guestbook_format_mysql_datetime($fromDate);
    }

    if ($toExclusive instanceof DateTimeImmutable) {
        $clauses[] = 'created_at < :to_date';
        $params[':to_date'] = guestbook_format_mysql_datetime($toExclusive);
    }

    if ($clauses === []) {
        return '';
    }

    return ' WHERE ' . implode(' AND ', $clauses);
}

function guestbook_mysql_index_exists(PDO $pdo, string $table, string $indexName): bool
{
    $stmt = $pdo->query("SHOW INDEX FROM `{$table}`");

    foreach ($stmt->fetchAll() as $row) {
        if ((string)($row['Key_name'] ?? '') === $indexName) {
            return true;
        }
    }

    return false;
}

function guestbook_mysql_ensure_schema(PDO $pdo): void
{
    static $migrated = false;

    if ($migrated) {
        return;
    }

    $table = guestbook_mysql_table_name();
    $columns = [];

    $stmt = $pdo->query("SHOW COLUMNS FROM `{$table}`");
    foreach ($stmt->fetchAll() as $row) {
        $columns[(string)($row['Field'] ?? '')] = strtolower((string)($row['Type'] ?? ''));
    }

    if (($columns['created_at'] ?? '') !== '' && !str_contains($columns['created_at'], 'datetime')) {
        $pdo->exec(
            "UPDATE `{$table}`
             SET `created_at` = COALESCE(
                 STR_TO_DATE(REPLACE(REPLACE(`created_at`, 'T', ' '), '+00:00', ''), '%Y-%m-%d %H:%i:%s'),
                 CURRENT_TIMESTAMP(3)
             )"
        );
        $pdo->exec("ALTER TABLE `{$table}` MODIFY `created_at` DATETIME(3) NOT NULL");
    }

    if (!isset($columns['updated_at'])) {
        $pdo->exec(
            "ALTER TABLE `{$table}`
             ADD COLUMN `updated_at` DATETIME(3) NOT NULL
             DEFAULT CURRENT_TIMESTAMP(3)
             ON UPDATE CURRENT_TIMESTAMP(3) AFTER `created_at`"
        );
    } elseif (!str_contains($columns['updated_at'], 'datetime')) {
        $pdo->exec("ALTER TABLE `{$table}` MODIFY `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3)");
    }

    if (!guestbook_mysql_index_exists($pdo, $table, 'idx_name_created_at')) {
        $pdo->exec("ALTER TABLE `{$table}` ADD INDEX `idx_name_created_at` (`name`, `created_at`)");
    }

    if (!guestbook_mysql_index_exists($pdo, $table, 'idx_updated_at')) {
        $pdo->exec("ALTER TABLE `{$table}` ADD INDEX `idx_updated_at` (`updated_at`)");
    }

    $migrated = true;
}

function guestbook_mysql_config(): array
{
    return [
        'host' => env_value('GUESTBOOK_DB_HOST', env_value('MYSQL_HOST', '127.0.0.1')),
        'port' => (int) env_value('GUESTBOOK_DB_PORT', env_value('MYSQL_PORT', '3306')),
        'name' => env_value('GUESTBOOK_DB_NAME', env_value('MYSQL_DATABASE', 'qaitest')),
        'user' => env_value('GUESTBOOK_DB_USER', env_value('MYSQL_USER', 'akusopo')),
        'password' => env_value('GUESTBOOK_DB_PASSWORD', env_value('MYSQL_PASSWORD', '')),
        'table' => env_value('GUESTBOOK_DB_TABLE', 'guestbook_entries'),
    ];
}

function guestbook_should_use_mysql(): bool
{
    $mode = strtolower(env_value('GUESTBOOK_STORAGE', 'mysql') ?? 'mysql');
    if ($mode !== 'mysql') {
        return false;
    }

    $config = guestbook_mysql_config();

    if ($config['name'] === '' || $config['user'] === '') {
        return false;
    }

    try {
        guestbook_mysql_pdo();
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

function guestbook_json_entries(): array
{
    $path = guestbook_storage_path();

    if (!is_file($path)) {
        return [];
    }

    $contents = file_get_contents($path);
    if ($contents === false || trim($contents) === '') {
        return [];
    }

    $decoded = json_decode($contents, true);
    if (!is_array($decoded)) {
        return [];
    }

    $entries = array_values(array_filter($decoded, static fn($entry) => is_array($entry)));
    return guestbook_sort_entries($entries, 'newest');
}

function guestbook_json_save(array $entries): void
{
    $path = guestbook_storage_path();
    $dir = dirname($path);

    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    file_put_contents(
        $path,
        json_encode(array_values($entries), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL,
        LOCK_EX
    );
}

function guestbook_normalize_db_row(array $row): array
{
    $createdAt = guestbook_parse_created_at((string)($row['created_at'] ?? ''));
    $updatedAt = guestbook_parse_created_at((string)($row['updated_at'] ?? ''));

    return [
        'id' => (string)($row['id'] ?? ''),
        'name' => (string)($row['name'] ?? ''),
        'message' => (string)($row['message'] ?? ''),
        'server_name' => (string)($row['server_name'] ?? ''),
        'request_uri' => (string)($row['request_uri'] ?? ''),
        'created_at' => $createdAt instanceof DateTimeImmutable ? guestbook_format_created_at($createdAt) : (string)($row['created_at'] ?? ''),
        'updated_at' => $updatedAt instanceof DateTimeImmutable ? guestbook_format_created_at($updatedAt) : (string)($row['updated_at'] ?? ''),
    ];
}

function guestbook_mysql_pdo(): PDO
{
    static $pdo = null;
    static $initialized = false;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $config = guestbook_mysql_config();
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        $config['host'],
        $config['port'],
        $config['name']
    );

    $pdo = new PDO($dsn, $config['user'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    if (!$initialized) {
        $table = guestbook_mysql_table_name();
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS `{$table}` (
                `id` char(32) NOT NULL,
                `name` varchar(255) NOT NULL,
                `message` text NOT NULL,
                `server_name` varchar(255) NOT NULL,
                `request_uri` varchar(2048) NOT NULL,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
                PRIMARY KEY (`id`),
                KEY `idx_created_at` (`created_at`),
                KEY `idx_name_created_at` (`name`, `created_at`),
                KEY `idx_updated_at` (`updated_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        guestbook_mysql_ensure_schema($pdo);
        $initialized = true;
    }

    return $pdo;
}

function guestbook_fetch_mysql_entries(?string $search = null, int $limit = 0, int $offset = 0, string $sort = 'newest', ?string $from = null, ?string $to = null): array
{
    $table = guestbook_mysql_table_name();
    $sql = "SELECT id, name, message, server_name, request_uri, created_at, updated_at FROM `{$table}`";
    $params = [];
    $sql .= guestbook_mysql_build_filters($search, $from, $to, $params);
    $sql .= ' ORDER BY ' . guestbook_mysql_order_clause($sort);

    if ($limit > 0) {
        $sql .= " LIMIT :limit OFFSET :offset";
    }

    $stmt = guestbook_mysql_pdo()->prepare($sql);

    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, PDO::PARAM_STR);
    }

    if ($limit > 0) {
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    }

    $stmt->execute();

    return array_map(
        static fn(array $row): array => guestbook_normalize_db_row($row),
        $stmt->fetchAll()
    );
}

function guestbook_fetch_mysql_count(?string $search = null, ?string $from = null, ?string $to = null): int
{
    $table = guestbook_mysql_table_name();
    $sql = "SELECT COUNT(*) AS total FROM `{$table}`";
    $params = [];
    $sql .= guestbook_mysql_build_filters($search, $from, $to, $params);

    $stmt = guestbook_mysql_pdo()->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, PDO::PARAM_STR);
    }
    $stmt->execute();

    $result = $stmt->fetch();

    return (int) ($result['total'] ?? 0);
}

function guestbook_save_mysql_entry(array $entry): void
{
    $table = guestbook_mysql_table_name();
    $createdAt = guestbook_parse_created_at((string)($entry['created_at'] ?? ''));
    $stmt = guestbook_mysql_pdo()->prepare(
        "INSERT INTO `{$table}` (id, name, message, server_name, request_uri, created_at)
         VALUES (:id, :name, :message, :server_name, :request_uri, :created_at)"
    );
    $stmt->execute([
        ':id' => substr((string)($entry['id'] ?? ''), 0, 32),
        ':name' => (string)($entry['name'] ?? ''),
        ':message' => (string)($entry['message'] ?? ''),
        ':server_name' => (string)($entry['server_name'] ?? ''),
        ':request_uri' => (string)($entry['request_uri'] ?? ''),
        ':created_at' => $createdAt instanceof DateTimeImmutable
            ? guestbook_format_mysql_datetime($createdAt)
            : (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s'),
    ]);
}

function guestbook_update_mysql_entry(string $id, string $name, string $message): bool
{
    $table = guestbook_mysql_table_name();
    $stmt = guestbook_mysql_pdo()->prepare(
        "UPDATE `{$table}` SET name = :name, message = :message WHERE id = :id"
    );
    $stmt->execute([
        ':id' => substr($id, 0, 32),
        ':name' => $name,
        ':message' => $message,
    ]);

    return $stmt->rowCount() > 0;
}

function guestbook_delete_mysql_entry(string $id): bool
{
    $table = guestbook_mysql_table_name();
    $stmt = guestbook_mysql_pdo()->prepare("DELETE FROM `{$table}` WHERE id = :id");
    $stmt->execute([':id' => substr($id, 0, 32)]);

    return $stmt->rowCount() > 0;
}

function guestbook_find_mysql_entry(string $id): ?array
{
    $table = guestbook_mysql_table_name();
    $stmt = guestbook_mysql_pdo()->prepare(
        "SELECT id, name, message, server_name, request_uri, created_at, updated_at FROM `{$table}` WHERE id = :id LIMIT 1"
    );
    $stmt->execute([':id' => substr($id, 0, 32)]);
    $row = $stmt->fetch();

    if ($row === false) {
        return null;
    }

    return guestbook_normalize_db_row($row);
}

function load_guestbook_entries(?string $search = null, int $limit = 0, int $offset = 0, string $sort = 'newest', ?string $from = null, ?string $to = null): array
{
    if (guestbook_should_use_mysql()) {
        return guestbook_fetch_mysql_entries($search, $limit, $offset, $sort, $from, $to);
    }

    $entries = guestbook_json_entries();
    $search = trim((string) $search);
    $sort = guestbook_normalize_sort($sort);

    if ($search !== '') {
        $entries = array_values(array_filter($entries, static function (array $entry) use ($search): bool {
            return stripos((string)($entry['name'] ?? ''), $search) !== false
                || stripos((string)($entry['message'] ?? ''), $search) !== false;
        }));
    }

    [$fromDate, $toExclusive] = guestbook_mysql_date_window($from, $to);
    $entries = guestbook_filter_entries($entries, $fromDate, $toExclusive);
    $entries = guestbook_sort_entries($entries, $sort);

    if ($limit > 0) {
        return array_slice($entries, $offset, $limit);
    }

    return $entries;
}

function count_guestbook_entries(?string $search = null, ?string $from = null, ?string $to = null): int
{
    if (guestbook_should_use_mysql()) {
        return guestbook_fetch_mysql_count($search, $from, $to);
    }

    return count(load_guestbook_entries($search, 0, 0, 'newest', $from, $to));
}

function save_guestbook_entries(array $entries): void
{
    if (guestbook_should_use_mysql()) {
        $config = guestbook_mysql_config();
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $config['table']);
        $pdo = guestbook_mysql_pdo();
        $pdo->exec("TRUNCATE TABLE `{$table}`");

        foreach ($entries as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            guestbook_save_mysql_entry(guestbook_normalize_db_row($entry));
        }

        return;
    }

    guestbook_json_save($entries);
}

function create_guestbook_entry(string $name, string $message, string $serverName, string $requestUri): array
{
    $createdAt = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format(DATE_ATOM);

    return [
        'id' => bin2hex(random_bytes(16)),
        'name' => $name,
        'message' => $message,
        'server_name' => $serverName,
        'request_uri' => $requestUri,
        'created_at' => $createdAt,
    ];
}

function append_guestbook_entry(array $entry): void
{
    if (guestbook_should_use_mysql()) {
        guestbook_save_mysql_entry($entry);
        return;
    }

    $entries = load_guestbook_entries();
    array_unshift($entries, $entry);
    save_guestbook_entries($entries);
}

function find_guestbook_entry_index(string $id): ?int
{
    foreach (load_guestbook_entries() as $index => $entry) {
        if (($entry['id'] ?? null) === $id) {
            return $index;
        }
    }

    return null;
}

function get_guestbook_entry(string $id): ?array
{
    if (guestbook_should_use_mysql()) {
        return guestbook_find_mysql_entry($id);
    }

    $index = find_guestbook_entry_index($id);
    if ($index === null) {
        return null;
    }

    $entries = load_guestbook_entries();
    return $entries[$index] ?? null;
}

function update_guestbook_entry(string $id, string $name, string $message): bool
{
    if (guestbook_should_use_mysql()) {
        return guestbook_update_mysql_entry($id, $name, $message);
    }

    $entries = load_guestbook_entries();

    foreach ($entries as $index => $entry) {
        if (($entry['id'] ?? null) !== $id) {
            continue;
        }

        $entries[$index]['name'] = $name;
        $entries[$index]['message'] = $message;
        save_guestbook_entries($entries);
        return true;
    }

    return false;
}

function delete_guestbook_entry(string $id): bool
{
    if (guestbook_should_use_mysql()) {
        return guestbook_delete_mysql_entry($id);
    }

    $entries = load_guestbook_entries();
    $nextEntries = [];
    $removed = false;

    foreach ($entries as $entry) {
        if (($entry['id'] ?? null) === $id) {
            $removed = true;
            continue;
        }

        $nextEntries[] = $entry;
    }

    if ($removed) {
        save_guestbook_entries($nextEntries);
    }

    return $removed;
}
