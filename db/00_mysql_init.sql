CREATE TABLE
    `runs` (
        id VARCHAR(36) NOT NULL,
        `created_at` TIMESTAMP(6) NOT NULL,
        `scheduled_for` TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
        `task` BLOB NOT NULL,
        `retry_policy` JSON,
        `status` enum(
            'created',
            'started',
            'finished'
        ) NOT NULL,
        PRIMARY KEY (`id`),
        KEY `idx_status_scheduled_for` (`status`, `scheduled_for`)
    );

CREATE TABLE
    `events` (
        `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        `run_id` VARCHAR(36) NOT NULL,
        `ts` TIMESTAMP(6) NOT NULL,
        `type` enum(
            'taskStarted',
            'taskFailed',
            'taskCompleted',
            'taskCrashed'
        ) NOT NULL,
        `task_id` varchar(36) NOT NULL,
        `payload` BLOB NOT NULL,
        `hash` varchar(32) NOT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `idx_hash` (`hash`),
        KEY `idx_run_id` (`run_id`)
    );