<?php

namespace Tsqm\Queue;

use DateTimeInterface;
use Tsqm\Task;

interface QueueInterface
{
    /**
     * @param string $taskName
     * @param string $taskId
     * @param DateTimeInterface $scheduledFor
     * @return void
     */
    public function enqueue(string $taskName, string $taskId, DateTimeInterface $scheduledFor): void;

    /**
     * @param string $taskName
     * @param callable(string $taskId): ?Task $callback
     * @return void
     */
    public function listen(string $taskName, callable $callback): void;
}
