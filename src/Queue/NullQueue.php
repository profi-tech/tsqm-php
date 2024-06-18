<?php

namespace Tsqm\Queue;

use DateTime;

class NullQueue implements QueueInterface
{
    public function enqueue(string $taskName, string $taskId, DateTime $scheduledFor): void
    {
        // Do nothing
    }

    public function listen(string $taskName, callable $callback): void
    {
        // Do nothing
    }
}
