<?php

namespace Tsqm\Tasks;

use DateTime;
use JsonSerializable;

class RetryPolicy2 implements JsonSerializable
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
        if ($retriesSoFar < $this->getMaxRetries()) {
            return (new DateTime())->modify('+' . $this->getMinInterval() . ' milliseconds');
        } else {
            return null;
        }
    }

    /**
     * @param array<string, mixed> $data
     * @return RetryPolicy2
     */
    public static function fromArray(array $data): self
    {
        return (new self())
            ->setMaxRetries($data['max_retries'])
            ->setMinInterval($data['min_interval']);
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
     * @return mixed
     */
    public function jsonSerialize()
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
