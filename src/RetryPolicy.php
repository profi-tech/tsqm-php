<?php

namespace Tsqm;

use DateTime;
use JsonSerializable;
use Tsqm\Errors\InvalidRetryPolicy;

class RetryPolicy implements JsonSerializable
{
    /** @var int Maximum number of retries */
    private int $maxRetries = 0;

    /** @var int Minimum time between retries in milliseconds */
    private int $minInterval = 100;

    /** @var float Exponential backoff factor */
    private float $backoffFactor = 1.0;

    /** @var bool Whether to use jitter */
    private bool $useJitter = false;

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
     * @param int|string $minInterval — miliseconds or a string that can be parsed by DateTime::modify
     * @return RetryPolicy
     */
    public function setMinInterval($minInterval): self
    {
        if (is_string($minInterval)) {
            $ref = microtime(true);
            $mod = (float)DateTime::createFromFormat('U.u', "$ref")->modify($minInterval)->format('U.u');
            $this->minInterval = (int)round(($mod - $ref) * 1000);

            return $this;
        } elseif (is_int($minInterval)) {
            $this->minInterval = $minInterval;
            return $this;
        } else {
            throw new InvalidRetryPolicy('Invalid minInterval value');
        }
    }

    public function getMinInterval(): int
    {
        return $this->minInterval;
    }

    public function setBackoffFactor(float $backoffFactor): self
    {
        if ($backoffFactor < 1) {
            throw new InvalidRetryPolicy('Backoff factor must be greater than or equal to 1');
        }
        $this->backoffFactor = $backoffFactor;
        return $this;
    }

    public function getBackoffFactor(): float
    {
        return $this->backoffFactor;
    }

    public function setUseJitter(bool $useJitter): self
    {
        $this->useJitter = $useJitter;
        return $this;
    }

    public function getUseJitter(): bool
    {
        return $this->useJitter;
    }

    public function getRetryAt(int $retriesSoFar): ?DateTime
    {
        if ($retriesSoFar < $this->getMaxRetries()) {
            $interval = $this->getMinInterval() * ($this->getBackoffFactor() ** $retriesSoFar);
            if ($this->useJitter) {
                $jitterFactor = mt_rand(500, 1500) / 1000; // Jitter from 0.5 to 1.5 times the interval
                $interval = round($interval * $jitterFactor);
            }
            return (new DateTime())->modify("+$interval milliseconds");
        } else {
            return null;
        }
    }

    /**
     * @param array<string, mixed> $data
     * @return RetryPolicy
     */
    public static function fromArray(array $data)
    {
        $policy = new RetryPolicy();
        if (isset($data['maxRetries'])) {
            $policy->setMaxRetries($data['maxRetries']);
        }
        if (isset($data['minInterval'])) {
            $policy->setMinInterval($data['minInterval']);
        }
        if (isset($data['backoffFactor'])) {
            $policy->setBackoffFactor($data['backoffFactor']);
        }
        if (isset($data['useJitter'])) {
            $policy->setUseJitter($data['useJitter']);
        }
        return $policy;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->jsonSerialize();
    }

    /**
     * @return mixed
     */
    public function jsonSerialize()
    {
        return [
            'maxRetries' => $this->getMaxRetries(),
            'minInterval' => $this->getMinInterval(),
            'backoffFactor' => $this->getBackoffFactor(),
            'useJitter' => $this->getUseJitter(),
        ];
    }
}
