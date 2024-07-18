<?php

namespace Tsqm;

use DateTime;

interface TsqmInterface
{
    public function runTask(Task $task, bool $async = false): Task;

    public function getTask(string $id): ?Task;

    /**
     * @return array<Task>
     */
    public function getScheduledTasks(int $limit = 100, ?DateTime $now = null): array;

    public function pollScheduledTasks(int $limit = 100, int $delay = 0, int $emptySleep = 10): void;

    public function listenQueuedTasks(string $taskName): void;
}
