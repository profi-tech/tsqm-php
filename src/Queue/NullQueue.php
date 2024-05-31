<?php

namespace Tsqm\Queue;

use Tsqm\Runs\Run;

class NullQueue implements QueueInterface
{
    public function enqueue(Run $run)
    {
    }
}
