
CREATE TABLE
    `tsqm_tasks` (
        `id` INTEGER PRIMARY KEY AUTOINCREMENT,
        `parent_id` INTEGER NOT NULL DEFAULT 0,
        `trans_id` INTEGER NOT NULL DEFAULT 0,
        `created_at` TIMESTAMP(6) NOT NULL,
        `scheduled_for` TIMESTAMP(6) NOT NULL,
        `started_at` TIMESTAMP(6),
        `finished_at` TIMESTAMP(6),
        `name` VARCHAR(255) NOT NULL,
        `args` BLOB,
        `result` BLOB,
        `error` BLOB,
        `retry_policy` JSON,
        `retried` INTEGER NOT NULL DEFAULT 0,
        `hash` VARCHAR(32) NOT NULL
    );

CREATE INDEX `idx_trans_id` ON `tsqm_tasks` (`trans_id`);
CREATE INDEX `idx_scheduled_for` ON `tsqm_tasks` (`scheduled_for`);
CREATE UNIQUE INDEX `idx_hash` ON `tsqm_tasks` (`hash`);

