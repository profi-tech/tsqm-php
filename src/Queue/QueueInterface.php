<?php

namespace Tsqm\Queue;

use DateTime;

interface QueueInterface
{
    public function enqueue(int $taskId, DateTime $scheduledFor): void;
}
