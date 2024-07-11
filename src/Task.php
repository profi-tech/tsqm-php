<?php

namespace Tsqm;

use DateTime;
use Exception;
use JsonSerializable;
use Tsqm\Errors\InvalidTask;
use Tsqm\Helpers\SerializationHelper;
use Tsqm\Helpers\UuidHelper;

class Task implements JsonSerializable
{
    private int $nid = 0;
    private ?string $id = null;
    private ?string $parent_id = null;
    private ?string $root_id = null;
    private ?DateTime $createdAt = null;
    private ?DateTime $scheduledFor = null;
    private ?string $waitInterval = null;
    private ?DateTime $startedAt = null;
    private ?DateTime $finishedAt = null;
    private ?string $name = null;
    /** @var array<mixed> */
    private array $args = [];
    private bool $isSecret = false;
    /** @var mixed */
    private $result = null;
    private ?Exception $error = null;
    private ?RetryPolicy $retryPolicy = null;
    private int $retried = 0;
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

    public function setNid(int $nid): self
    {
        $this->nid = $nid;
        return $this;
    }

    public function setId(string $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getId(): string
    {
        if (is_null($this->id)) {
            throw new InvalidTask("ID is required");
        }
        return $this->id;
    }

    public function getDeterminedUuid(): string
    {
        return UuidHelper::named(implode('::', [
            $this->parent_id,
            $this->root_id,
            $this->name,
            SerializationHelper::serialize($this->args),
        ]));
    }

    public function setParentId(string $parent_id): self
    {
        $this->parent_id = $parent_id;
        return $this;
    }

    public function getParentId(): ?string
    {
        return $this->parent_id;
    }

    public function setRootId(string $root_id): self
    {
        $this->root_id = $root_id;
        return $this;
    }

    public function getRootId(): string
    {
        if ($this->isNullRoot()) {
            throw new InvalidTask("Root ID is required");
        }
        return $this->root_id;
    }

    public function isNullRoot(): bool
    {
        return is_null($this->root_id);
    }

    public function isRoot(): bool
    {
        return $this->getId() === $this->getRootId();
    }

    public function setCreatedAt(?DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function isCreated(): bool
    {
        return !is_null($this->createdAt);
    }

    public function getCreatedAt(): ?DateTime
    {
        if (!$this->isCreated()) {
            throw new InvalidTask("Task was not created yet");
        }
        return $this->createdAt;
    }

    public function setScheduledFor(DateTime $scheduledFor): self
    {
        $this->scheduledFor = $scheduledFor;
        return $this;
    }

    public function isNullScheduledFor(): bool
    {
        return is_null($this->scheduledFor);
    }

    public function getScheduledFor(): ?DateTime
    {
        if ($this->isNullScheduledFor()) {
            throw new InvalidTask("Task scheduled for is required");
        }
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

    public function setStartedAt(?DateTime $startedAt): self
    {
        $this->startedAt = $startedAt;
        return $this;
    }

    public function getStartedAt(): ?DateTime
    {
        return $this->startedAt;
    }

    public function setFinishedAt(?DateTime $finishedAt): self
    {
        $this->finishedAt = $finishedAt;
        return $this;
    }

    public function isFinished(): bool
    {
        return !is_null($this->finishedAt);
    }

    public function getFinishedAt(): ?DateTime
    {
        return $this->finishedAt;
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
        return $this->name . (!is_null($this->id) ? " {$this->id}" : "");
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

    /**
     * @param mixed $result
     */
    public function setResult($result): self
    {
        $this->result = $result;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getResult()
    {
        return $this->result;
    }

    public function setError(?Exception $error): self
    {
        $this->error = $error;
        return $this;
    }

    public function hasError(): bool
    {
        return !is_null($this->error);
    }

    public function getError(): ?Exception
    {
        return $this->error;
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

    public function setRetried(int $retried): self
    {
        $this->retried = $retried;
        return $this;
    }

    public function incRetried(): self
    {
        $this->retried++;
        return $this;
    }

    public function getRetried(): int
    {
        return $this->retried;
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
     * @return mixed
     */
    public function jsonSerialize()
    {
        $timestampFormat = "Y-m-d H:i:s.u";

        return [
            'nid' => $this->nid,
            'id' => $this->id,
            'parent_id' => $this->parent_id,
            'root_id' => $this->root_id,
            'created_at' => $this->createdAt ? $this->createdAt->format($timestampFormat) : null,
            'scheduled_for' => $this->scheduledFor ? $this->scheduledFor->format($timestampFormat) : null,
            'started_at' => $this->startedAt ? $this->startedAt->format($timestampFormat) : null,
            'finished_at' => $this->finishedAt ? $this->finishedAt->format($timestampFormat) : null,
            'name' => $this->name,
            'is_secret' => $this->isSecret,
            'args' => $this->args ? $this->hideSecret($this->args) : null,
            'result' => $this->hideSecret($this->result),
            'error' => $this->error ? [
                'class' => get_class($this->error),
                'message' => $this->error->getMessage(),
                'code' => $this->error->getCode(),
            ] : null,
            'retry_policy' => $this->retryPolicy,
            'retried' => $this->retried,
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
