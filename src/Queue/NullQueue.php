<?php

namespace Tsqm\Queue;

use DateTime;

class NullQueue implements QueueInterface
{
    public function enqueue(string $taskId, DateTime $scheduledFor): void
    {
        // Do nothing
    }
}
