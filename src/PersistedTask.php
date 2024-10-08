<?php

namespace Tsqm;

use DateTime;
use Exception;
use JsonSerializable;
use Tsqm\Errors\InvalidTask;
use Tsqm\Helpers\SerializationHelper;
use Tsqm\Helpers\UuidHelper;

class PersistedTask implements JsonSerializable
{
    private int $nid = 0;

    private ?string $id = null;

    private ?string $parentId = null;

    private ?string $rootId = null;

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

    public static function fromTask(Task $task): self
    {
        return (new self())
            ->setScheduledFor($task->getScheduledFor())
            ->setWaitInterval($task->getWaitInterval())
            ->setName($task->getName())
            ->setArgs(...$task->getArgs())
            ->setIsSecret($task->getIsSecret())
            ->setRetryPolicy($task->getRetryPolicy())
            ->setTrace($task->getTrace());
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
            $this->parentId,
            $this->rootId,
            $this->name,
            SerializationHelper::serialize($this->args),
        ]));
    }

    public function setParentId(string $parent_id): self
    {
        $this->parentId = $parent_id;
        return $this;
    }

    public function getParentId(): ?string
    {
        return $this->parentId;
    }

    public function setRootId(string $root_id): self
    {
        $this->rootId = $root_id;
        return $this;
    }

    public function getRootId(): string
    {
        if (!$this->hasRoot()) {
            throw new InvalidTask("Root ID is required");
        }
        return $this->rootId;
    }

    public function hasRoot(): bool
    {
        return !is_null($this->rootId);
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

    public function setScheduledFor(?DateTime $scheduledFor): self
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
        if (!$this->isScheduled()) {
            throw new InvalidTask("Task was not scheduled yet");
        }
        return $this->scheduledFor;
    }

    public function setWaitInterval(?string $waitInterval): self
    {
        $this->waitInterval = $waitInterval;
        return $this;
    }

    public function hasWaitInterval(): bool
    {
        return !is_null($this->waitInterval);
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

    public function isStarted(): bool
    {
        return !is_null($this->startedAt);
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

    public function setRetryPolicy(?RetryPolicy $retryPolicy): self
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

    public function hasTrace(): bool
    {
        return !is_null($this->trace);
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        $timestampFormat = "Y-m-d H:i:s.u";

        return [
            'nid' => $this->nid,
            'id' => $this->id,
            'parent_id' => $this->parentId,
            'root_id' => $this->rootId,
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
