CREATE TABLE IF NOT EXISTS `guestbook_entries` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
