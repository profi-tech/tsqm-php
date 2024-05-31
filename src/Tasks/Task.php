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

    /** @var array<mixed> */
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

    public function getId(): string
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

    /**
     * @param array<mixed> $args
     * @return Task
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

    public function setScheduledFor(DateTime $scheduledFor): self
    {
        $this->scheduledFor = $scheduledFor;
        return $this;
    }

    public function getScheduledFor(): ?DateTime
    {
        return $this->scheduledFor;
    }

    /**
     * @return array<string, mixed>
     */
    public function __serialize(): array
    {
        return [
            'name' => $this->name,
            'args' => $this->args,
            'scheduledFor' => $this->scheduledFor ? $this->scheduledFor->format('Y-m-d H:i:s.v') : null,
            'retryPolicy' => $this->retryPolicy
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->__serialize();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function __unserialize(array $data): void
    {
        $this->name = $data['name'];
        $this->args = $data['args'];
        $this->scheduledFor = $data['scheduledFor'] ? new DateTime($data['scheduledFor']) : null;
        $this->retryPolicy = $data['retryPolicy'];
    }
}
