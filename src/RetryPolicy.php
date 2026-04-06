<?php

namespace Tsqm;

use Carbon\CarbonImmutable;
use DateTimeInterface;
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
     * @param int|string $minInterval — milliseconds or a string that can be parsed by CarbonImmutable::modify
     * @return RetryPolicy
     */
    public function setMinInterval($minInterval): self
    {
        if (is_string($minInterval)) {
            $now = CarbonImmutable::now();
            $nowTs = (float) $now->format('U.u');
            $modTs = (float) $now->modify($minInterval)->format('U.u');
            $this->minInterval = (int) round(($modTs - $nowTs) * 1000);

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

    public function getRetryAt(int $retriesSoFar): ?DateTimeInterface
    {
        if ($retriesSoFar < $this->getMaxRetries()) {
            $interval = $this->getMinInterval() * ($this->getBackoffFactor() ** $retriesSoFar);
            if ($this->useJitter) {
                $jitterFactor = mt_rand(500, 1500) / 1000; // Jitter from 0.5 to 1.5 times the interval
                $interval = round($interval * $jitterFactor);
            }
            return CarbonImmutable::now()->addMilliseconds((int) $interval);
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
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'maxRetries' => $this->getMaxRetries(),
            'minInterval' => $this->getMinInterval(),
            'backoffFactor' => $this->getBackoffFactor(),
            'useJitter' => $this->getUseJitter(),
        ];
    }
}
