<?php

namespace Tsqm;

use DateTime;

interface SchedulerInterface
{
    public function schedule(Run $run, DateTime $scheduleFor);
}
