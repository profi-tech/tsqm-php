<?php

namespace Tsqm;

use DateTime;
use JsonSerializable;

class RetryPolicy implements JsonSerializable
{
    /** @var int Maximum number of retries */
    private int $maxRetries = 0;

    /** @var int Minimum time between retries in milliseconds */
    private int $minInterval = 100;

    public function setMaxRetries(int $maxRetries): self
    {
        $this->maxRetries = $maxRetries;
        return $this;
    }

    public function getMaxRetries(): int
    {
        return $this->maxRetries;
    }

    /**
     * Set the minimum time between retries in milliseconds
     * @param int $minInterval
     * @return RetryPolicy
     */
    public function setMinInterval(int $minInterval): self
    {
        $this->minInterval = $minInterval;
        return $this;
    }

    public function getMinInterval(): int
    {
        return $this->minInterval;
    }

    public function getRetryAt(int $retriesSoFar): ?DateTime
    {
        if ($retriesSoFar < $this->getMaxRetries()) {
            return (new DateTime())->modify('+' . $this->getMinInterval() . ' milliseconds');
        } else {
            return null;
        }
    }

    /**
     * @return array<string, int>
     */
    public function __serialize(): array
    {
        return [
            'maxRetries' => $this->getMaxRetries(),
            'minInterval' => $this->getMinInterval(),
        ];
    }

    /**
     * @param array<string, int> $data
     */
    public function __unserialize(array $data): void
    {
        $this->maxRetries = $data['maxRetries'];
        $this->minInterval = $data['minInterval'];
    }

    /**
     * @return mixed
     */
    public function jsonSerialize()
    {
        return [
            'maxRetries' => $this->getMaxRetries(),
            'minInterval' => $this->getMinInterval(),
        ];
    }
}
