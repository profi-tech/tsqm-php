<?php

namespace Tsqm\Runs\Queue;

use Tsqm\Runs\Run;

interface RunQueueInterface
{
    public function enqueueRun(Run $run);
}
