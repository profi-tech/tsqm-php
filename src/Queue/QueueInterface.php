<?php

namespace Tsqm\Queue;

use DateTime;

interface QueueInterface
{
    public function enqueue(string $taskName, string $taskId, DateTime $scheduledFor): void;

    public function listen(string $taskName, callable $callback): void;
}
