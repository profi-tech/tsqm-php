<?php

namespace Tsqm\Runs\Queue;

use Tsqm\Runs\Run;

class NullQueue implements RunQueueInterface
{
    public function enqueueRun(Run $run)
    {
    }
}
