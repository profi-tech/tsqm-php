<?php

namespace Tsqm\Queue;

use Tsqm\Runs\Run;

interface QueueInterface
{
    public function enqueue(Run $run);
}
