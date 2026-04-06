<?php

namespace Tsqm\Queue;

use DateTimeInterface;

class NullQueue implements QueueInterface
{
    public function enqueue(string $taskName, string $taskId, DateTimeInterface $scheduledFor): void
    {
        // Do nothing
    }

    public function listen(string $taskName, callable $callback): void
    {
        // Do nothing
    }
}
