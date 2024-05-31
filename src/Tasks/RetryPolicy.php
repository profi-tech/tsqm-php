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

    public function setMaxRetries(int $maxRetries): self
    {
        $this->maxRetries = $maxRetries;
        return $this;
    }

    public function getMaxRetries(): int
    {
        return $this->maxRetries;
    }

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
        if ($retriesSoFar >= $this->getMaxRetries()) {
            return null;
        } else {
            return (new DateTime())->modify('+' . $this->getMinInterval() . ' milliseconds');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function __serialize(): array
    {
        return [
            'maxRetries' => $this->maxRetries,
            'minInterval' => $this->minInterval
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): mixed
    {
        return $this->__serialize();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function __unserialize(array $data): void
    {
        $this->maxRetries = $data['maxRetries'];
        $this->minInterval = $data['minInterval'];
    }
}
