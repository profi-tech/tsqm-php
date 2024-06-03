<?php

namespace Tsqm\Tasks;

use DateTime;
use JsonSerializable;
use Throwable;
use Tsqm\Errors\InvalidTask;
use Tsqm\Helpers\SerializationHelper;

class Task implements JsonSerializable
{
    private ?int $id = null;
    private int $parent_id = 0;
    private ?string $trans_id = null;
    private ?DateTime $createdAt = null;
    private ?DateTime $scheduledFor = null;
    private ?DateTime $startedAt = null;
    private ?DateTime $finishedAt = null;
    private ?string $name = null;
    /** @var array<mixed> */
    private array $args = [];
    /** @var mixed */
    private $result = null;
    private ?Throwable $error = null;
    private ?RetryPolicy $retryPolicy = null;
    private int $retried = 0;

    /**
     * @param mixed $callable
     * @return Task
     * @throws InvalidTask
     */
    public function setCallable($callable): self
    {
        $is_invokable = is_object($callable) && method_exists($callable, '__invoke');
        if ($is_invokable) {
            $this->name = get_class($callable);
        } else {
            throw new InvalidTask("Callable object with __invoke method");
        }
        return $this;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setParentId(int $parent_id): self
    {
        $this->parent_id = $parent_id;
        return $this;
    }

    public function getParentId(): int
    {
        return $this->parent_id;
    }

    public function setTransId(string $trans_id): self
    {
        $this->trans_id = $trans_id;
        return $this;
    }

    public function getTransId(): ?string
    {
        return $this->trans_id;
    }

    public function setCreatedAt(?DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getCreatedAt(): ?DateTime
    {
        return $this->createdAt;
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
        return $this->name;
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

    public function setError(?Throwable $error): self
    {
        $this->error = $error;
        return $this;
    }

    public function hasError(): bool
    {
        return !is_null($this->error);
    }

    public function getError(): ?Throwable
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

    public function incRetried(): self
    {
        $this->retried++;
        return $this;
    }

    public function getRetried(): int
    {
        return $this->retried;
    }

    public function getHash(): string
    {
        return md5(implode('::', [
            $this->trans_id,
            $this->name,
            SerializationHelper::serialize($this->args),
        ]));
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $task = new self();
        if (isset($data['id'])) {
            $task->setId((int)$data['id']);
        }
        if (isset($data['parent_id'])) {
            $task->setParentId((int)$data['parent_id']);
        }
        if (isset($data['trans_id'])) {
            $task->setTransId($data['trans_id']);
        }
        if (isset($data['created_at'])) {
            $task->setCreatedAt(new DateTime($data['created_at']));
        }
        if (isset($data['scheduled_for'])) {
            $task->setScheduledFor(new DateTime($data['scheduled_for']));
        }
        if (isset($data['started_at'])) {
            $task->setStartedAt(new DateTime($data['started_at']));
        }
        if (isset($data['finished_at'])) {
            $task->setFinishedAt(new DateTime($data['finished_at']));
        }
        if (isset($data['name'])) {
            $task->setName($data['name']);
        }
        if (isset($data['args'])) {
            $task->setArgs(...SerializationHelper::unserialize($data['args']));
        }
        if (isset($data['result'])) {
            $task->setResult(SerializationHelper::unserialize($data['result']));
        }
        if (isset($data['error'])) {
            $task->setError(SerializationHelper::unserialize($data['error']));
        }
        if (isset($data['retry_policy'])) {
            $task->setRetryPolicy(RetryPolicy::fromArray(json_decode($data['retry_policy'], true)));
        }
        if (isset($data['retried'])) {
            $task->retried = $data['retried'];
        }

        return $task;
    }

    /**
     * @return mixed
     */
    public function jsonSerialize()
    {
        return [
            'id' => $this->id,
            'parent_id' => $this->parent_id,
            'trans_id' => $this->trans_id,
            'created_at' => $this->createdAt ? $this->createdAt->format(DateTime::ATOM) : null,
            'scheduled_for' => $this->scheduledFor ? $this->scheduledFor->format(DateTime::ATOM) : null,
            'started_at' => $this->startedAt ? $this->startedAt->format(DateTime::ATOM) : null,
            'finished_at' => $this->finishedAt ? $this->finishedAt->format(DateTime::ATOM) : null,
            'name' => $this->name,
            'args' => $this->args ?: null,
            'result' => $this->result,
            'error' => $this->error ? [
                'class' => get_class($this->error),
                'message' => $this->error->getMessage(),
                'code' => $this->error->getCode(),
                'file' => $this->error->getFile(),
                'line' => $this->error->getLine(),
            ] : null,
            'retry_policy' => $this->retryPolicy,
            'retried' => $this->retried,
        ];
    }
}
