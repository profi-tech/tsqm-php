<?php
namespace Tsqm;

use DateTime;
use Exception;
use JsonSerializable;
use Tsqm\Helpers\UuidHelper;

class Task implements JsonSerializable {
    private string $name;
    private array $args = [];
    private ?RetryPolicy $retryPolicy = null;
    private ?DateTime $scheduledFor = null;
    private bool $forceAsync = false;

    public function __construct(object $callable) {
        if (is_object($callable) && method_exists($callable, '__invoke')) {
            $this->name = get_class($callable);
        }
        else {
            throw new Exception("Callable must be a string or an object with __invoke method");
        }
    }

    public function getId() {
        return UuidHelper::named(implode('::', [
            $this->name,
            serialize($this->args),
        ]));
    }
    
    public function getName(): string {
        return $this->name;
    }

    public function setArgs(...$args): self {
        $this->args = $args;
        return $this;
    }

    public function getArgs(): array {
        return $this->args;
    }

    public function setRetryPolicy(RetryPolicy $retryPolicy): self {
        $this->retryPolicy = $retryPolicy;
        return $this;
    }

    public function getRetryPolicy(): ?RetryPolicy {
        return $this->retryPolicy;
    }

    public function setScheduledFor(DateTime $scheduledFor): self {
        $this->scheduledFor = $scheduledFor;
        return $this;
    }

    public function getScheduledFor(): ?DateTime {
        return $this->scheduledFor;
    }

    public function setForceAsync(bool $forceAsync): self {
        $this->forceAsync = $forceAsync;
        return $this;
    }

    public function getForceAsync(): bool {
        return $this->forceAsync;
    }

    public function __serialize(): array
    {
        return [
            'name' => $this->name,
            'args' => $this->args,
            'scheduledFor' => $this->scheduledFor,
            'forceAsync' => $this->forceAsync,
            'retryPolicy' => $this->retryPolicy
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->__serialize();
    }

    public function __unserialize(array $data): void
    {
        $this->name = $data['callable'];
        $this->args = $data['args'];
        $this->scheduledFor = $data['scheduledFor'];
        $this->forceAsync = $data['forceAsync'];
        $this->retryPolicy = $data['retryPolicy'];
    }

}