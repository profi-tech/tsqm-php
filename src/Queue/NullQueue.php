<?php

namespace Tsqm\Queue;

use DateTime;

class NullQueue implements QueueInterface
{
    public function enqueue(int $taskId, DateTime $scheduledFor): void
    {
        // Do nothing
    }
}
