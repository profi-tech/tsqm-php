<?php

namespace Tsqm\Tasks;

use DateTime;
use JsonSerializable;
use Tsqm\Errors\InvalidTask;
use Tsqm\Helpers\SerializationHelper;
use Tsqm\Helpers\UuidHelper;

class Task implements JsonSerializable
{
    private string $name;
    private array $args = [];
    private ?RetryPolicy $retryPolicy = null;
    private ?DateTime $scheduledFor = null;

    public function __construct(object $callable)
    {
        if (is_object($callable) && method_exists($callable, '__invoke')) {
            $this->name = get_class($callable);
        } else {
            throw new InvalidTask("Callable must be a string or an object with __invoke method");
        }
    }

    public function getId()
    {
        return UuidHelper::named(implode('::', [
            $this->name,
            SerializationHelper::serialize($this->args),
        ]));
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setArgs(...$args): self
    {
        $this->args = $args;
        return $this;
    }

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

    public function setScheduledFor(DateTime $scheduledFor): self
    {
        $this->scheduledFor = $scheduledFor;
        return $this;
    }

    public function getScheduledFor(): ?DateTime
    {
        return $this->scheduledFor;
    }

    public function __serialize(): array
    {
        return [
            'name' => $this->name,
            'args' => $this->args,
            'scheduledFor' => $this->scheduledFor ? $this->scheduledFor->format('Y-m-d H:i:s.v') : null,
            'retryPolicy' => $this->retryPolicy
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->__serialize();
    }

    public function __unserialize(array $data): void
    {
        $this->name = $data['name'];
        $this->args = $data['args'];
        $this->scheduledFor = $data['scheduledFor'] ? new DateTime($data['scheduledFor']) : null;
        $this->retryPolicy = $data['retryPolicy'];
    }
}
