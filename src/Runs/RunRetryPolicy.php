<?php

namespace Tsqm\Runs;

use DateTime;
use JsonSerializable;

class RunRetryPolicy implements JsonSerializable
{
    private int $maxRetries = 0;

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

    public function jsonSerialize()
    {
        return [
            'maxRetries' => $this->getMaxRetries(),
            'minInterval' => $this->getMinInterval(),
        ];
    }

    public static function fromArray(array $data): RunRetryPolicy
    {
        $retryPolicy = new RunRetryPolicy();
        if (isset($data['maxRetries'])) {
            $retryPolicy->setMaxRetries($data['maxRetries']);
        }
        if (isset($data['minInterval'])) {
            $retryPolicy->setMinInterval($data['minInterval']);
        }
        return $retryPolicy;
    }
}
