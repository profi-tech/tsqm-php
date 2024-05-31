<?php

namespace Tsqm\Tasks;

use DateTime;
use JsonSerializable;

class RetryPolicy implements JsonSerializable
{
    /** @var int Maximum number of retries */
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

    public function __serialize(): array
    {
        return [
            'maxRetries' => $this->maxRetries,
            'minInterval' => $this->minInterval
        ];
    }

    public function jsonSerialize(): mixed
    {
        return $this->__serialize();
    }

    public function __unserialize(array $data): void
    {
        $this->maxRetries = $data['maxRetries'];
        $this->minInterval = $data['minInterval'];
    }
}
