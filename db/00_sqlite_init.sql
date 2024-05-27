CREATE TABLE
    `runs` (
        id VARCHAR(36) PRIMARY KEY NOT NULL,
        `created_at` TIMESTAMP(6) NOT NULL,
        `scheduled_for` TIMESTAMP(3) NOT NULL,
        `task` BLOB NOT NULL,
        `retry_policy` JSON,
        `status` VARCHAR(32) NOT NULL
    );

CREATE INDEX
    `idx_status_scheduled_for` ON `runs` (`status`, `scheduled_for`);

CREATE TABLE
    `events` (
        `id` INTEGER PRIMARY KEY AUTOINCREMENT,
        `run_id` VARCHAR(36) NOT NULL,
        `ts` TIMESTAMP(6) NOT NULL,
        `type` VARCHAR(32) NOT NULL,
        `task_id` VARCHAR(36) NOT NULL,
        `payload` BLOB NOT NULL,
        `hash` VARCHAR(32) NOT NULL
    );

CREATE INDEX `idx_run_id` ON `events` (`run_id`);

CREATE UNIQUE INDEX `idx_hash` ON `events` (`hash`);