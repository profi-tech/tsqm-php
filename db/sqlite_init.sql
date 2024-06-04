
CREATE TABLE
    `tsqm_tasks` (
        `n` INTEGER PRIMARY KEY AUTOINCREMENT,
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
        `retried` INTEGER NOT NULL DEFAULT 0
    );

CREATE UNIQUE INDEX `idx_id` ON `tsqm_tasks` (`id`);
CREATE INDEX `idx_root_id` ON `tsqm_tasks` (`root_id`);
CREATE INDEX `idx_scheduled_for` ON `tsqm_tasks` (`scheduled_for`);
