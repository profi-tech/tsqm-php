<?php

namespace Tsqm\Runs;

use DateTime;

interface RunSchedulerInterface
{
    public function scheduleRun(Run $run, DateTime $scheduleFor);
}
