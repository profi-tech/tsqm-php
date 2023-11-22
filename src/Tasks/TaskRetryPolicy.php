<?php

namespace Tsqm\Tasks;

use DateTime;

class TaskRetryPolicy
{
    private int $maxRetries = 3;

    /** @var int Minimum time between retries in milliseconds */
    private int $minInterval = 100;

    public function setMaxRetries(int $maxRetries)
    {
        $this->maxRetries = $maxRetries;
        return $this;
    }

    public function getMaxRetries()
    {
        return $this->maxRetries;
    }

    public function setMinInterval(int $minInterval)
    {
        $this->minInterval = $minInterval;
        return $this;
    }

    public function getMinInterval()
    {
        return $this->minInterval;
    }

    public function getRetryAt(int $retriesSoFar): ?DateTime
    {
        if ($retriesSoFar >= $this->getMaxRetries()) {
            return null;
        } else {
            return (new DateTime())->modify('+' . $this->getMinInterval() . ' milliseconds');
        }
    }
}
