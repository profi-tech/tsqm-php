<?php

namespace Tsqm;

use DateTime;
use JsonSerializable;
use Tsqm\Errors\InvalidTask;

class Task implements JsonSerializable
{
    private ?DateTime $scheduledFor = null;

    private ?string $waitInterval = null;

    private ?string $name = null;

    /** @var array<mixed> */
    private array $args = [];

    private bool $isSecret = false;

    private ?RetryPolicy $retryPolicy = null;

    /** @var mixed */
    private $trace = null;

    /**
     * @return Task
     * @throws InvalidTask
     */
    public function setCallable(callable $callable): self
    {
        if (is_object($callable) && method_exists($callable, '__invoke')) {
            $this->name = get_class($callable);
        } elseif (is_array($callable) && method_exists($callable[0], $callable[1])) {
            $this->name = implode('::', $callable);
        } elseif (is_string($callable)) {
            $this->name = $callable;
        } else {
            throw new InvalidTask("Callable must be an object with __invoke method, named function or static method");
        }
        return $this;
    }

    public function setScheduledFor(DateTime $scheduledFor): self
    {
        $this->scheduledFor = $scheduledFor;
        return $this;
    }

    public function isScheduled(): bool
    {
        return !is_null($this->scheduledFor);
    }

    public function getScheduledFor(): ?DateTime
    {
        return $this->scheduledFor;
    }

    public function setWaitInterval(string $waitInterval): self
    {
        $this->waitInterval = $waitInterval;
        return $this;
    }

    public function isNullWaitInterval(): bool
    {
        return is_null($this->waitInterval);
    }

    public function getWaitInterval(): ?string
    {
        return $this->waitInterval;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getName(): string
    {
        if (is_null($this->name)) {
            throw new InvalidTask("Task name is required");
        }
        return $this->name;
    }

    public function getLogId(): string
    {
        return $this->name;
    }

    public function setIsSecret(bool $isSecret): self
    {
        $this->isSecret = $isSecret;
        return $this;
    }

    public function getIsSecret(): bool
    {
        return $this->isSecret;
    }

    /**
     * @param array<mixed> $args
     */
    public function setArgs(...$args): self
    {
        $this->args = $args;
        return $this;
    }

    /**
     * @return array<mixed>
     */
    public function getArgs(): array
    {
        return $this->args;
    }

    public function setRetryPolicy(RetryPolicy $retryPolicy): self
    {
        $this->retryPolicy = $retryPolicy;
        return $this;
    }

    public function getRetryPolicy(): ?RetryPolicy
    {
        return $this->retryPolicy;
    }

    /**
     * @param mixed $trace
     */
    public function setTrace($trace): self
    {
        $this->trace = $trace;
        return $this;
    }

    /**
     * @return mixed|null
     */
    public function getTrace()
    {
        return $this->trace;
    }

    public function isNullTrace(): bool
    {
        return is_null($this->trace);
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        $timestampFormat = "Y-m-d H:i:s.u";

        return [
            'scheduled_for' => $this->scheduledFor ? $this->scheduledFor->format($timestampFormat) : null,
            'name' => $this->name,
            'is_secret' => $this->isSecret,
            'args' => $this->args ? $this->hideSecret($this->args) : null,
            'retry_policy' => $this->retryPolicy,
            'trace' => $this->trace,
        ];
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    private function hideSecret($value)
    {
        if (!is_null($value)) {
            return $this->isSecret ? '***' : $value;
        } else {
            return $value;
        }
    }
}
