CREATE TABLE `runs` (
    id VARCHAR(36) NOT NULL,
    `created_at` TIMESTAMP(6) NOT NULL,
    `run_at` TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `task` BLOB NOT NULL,
    `status` enum(
        'created',
        'started',
        'finished'
    ) NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_status_run_at` (`status`, `run_at`)
);

CREATE TABLE `events` (
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

CREATE TABLE `tasks` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `trans_id` varchar(36) NOT NULL,
    `created_at` TIMESTAMP(6) NOT NULL,
    `scheduled_for` TIMESTAMP(3) NOT NULL,
    `started_at` TIMESTAMP(3),
    `finished_at` TIMESTAMP(3),
    `name` VARCHAR(255) NOT NULL,
    `args` BLOB,
    `retry_policy` JSON,
    `result` BLOB,
    `error` BLOB,
    `hash` varchar(32) NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_trans_id` (`trans_id`),
    KEY `idx_scheduled_for` (`scheduled_for`),
    UNIQUE KEY `idx_hash` (`hash`)
);