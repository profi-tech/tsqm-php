
CREATE TABLE `tsqm_tasks` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `parent_id` BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `trans_id` varchar(36) NOT NULL,
    `created_at` TIMESTAMP(6) NOT NULL,
    `scheduled_for` TIMESTAMP(6) NOT NULL,
    `started_at` TIMESTAMP(6),
    `finished_at` TIMESTAMP(6),
    `name` VARCHAR(255) NOT NULL,
    `args` BLOB,
    `result` BLOB,
    `error` BLOB,
    `retry_policy` JSON,
    `retried` INT UNSIGNED NOT NULL DEFAULT 0,
    `hash` varchar(32) NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_trans_id` (`trans_id`),
    KEY `idx_scheduled_for` (`scheduled_for`),
    UNIQUE KEY `idx_hash` (`hash`)
);