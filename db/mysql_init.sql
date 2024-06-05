
CREATE TABLE `tsqm_tasks` (
    `nid` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `id` VARCHAR(36) NOT NULL,
    `parent_id` VARCHAR(36),
    `root_id` VARCHAR(36) NOT NULL,
    `created_at` TIMESTAMP(6) NOT NULL,
    `scheduled_for` TIMESTAMP(6) NOT NULL,
    `started_at` TIMESTAMP(6),
    `finished_at` TIMESTAMP(6),
    `name` VARCHAR(255) NOT NULL,
    `args` BLOB,
    `result` BLOB,
    `error` BLOB,
    `retry_policy` JSON,
    `retried` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    UNIQUE KEY (`id`),
    KEY `idx_parent_id` (`parent_id`),
    KEY `idx_root_id` (`root_id`),
    KEY `idx_scheduled_for` (`scheduled_for`)
);
