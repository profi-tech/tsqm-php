<?php

namespace Tsqm\Tasks;

use DateTime;
use JsonSerializable;
use Throwable;
use Tsqm\Errors\InvalidTask;
use Tsqm\Helpers\SerializationHelper;

class Task2 implements JsonSerializable
{
    private ?int $id = null;
    private ?string $trans_id = null;
    private ?DateTime $createdAt = null;
    private ?DateTime $scheduledFor = null;
    private ?DateTime $startedAt = null;
    private ?DateTime $finishedAt = null;
    private ?string $name = null;
    /** @var array<mixed> */
    private array $args = [];
    private ?RetryPolicy2 $retryPolicy = null;
    /** @var mixed */
    private $result = null;
    private ?Throwable $error = null;

    /**
     * @param mixed $callable
     * @return Task2
     * @throws InvalidTask
     */
    public function setCallable(mixed $callable): self
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

    public function setRetryPolicy(RetryPolicy2 $retryPolicy): self
    {
        $this->retryPolicy = $retryPolicy;
        return $this;
    }

    public function getRetryPolicy(): ?RetryPolicy2
    {
        return $this->retryPolicy;
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

    public function setError(Throwable $error): self
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

    public function getHash(): string
    {
        return md5(implode('::', [
            $this->trans_id,
            $this->name,
            SerializationHelper::serialize($this->args),
        ]));
    }

    /**
     * @return mixed
     */
    public function jsonSerialize()
    {
        return array_filter([
            'id' => $this->id,
            'trans_id' => $this->trans_id,
            'created_at' => $this->createdAt ? $this->createdAt->format(DateTime::ATOM) : null,
            'scheduled_for' => $this->scheduledFor ? $this->scheduledFor->format(DateTime::ATOM) : null,
            'started_at' => $this->startedAt ? $this->startedAt->format(DateTime::ATOM) : null,
            'finished_at' => $this->finishedAt ? $this->finishedAt->format(DateTime::ATOM) : null,
            'name' => $this->name,
            'args' => $this->args ?: null,
            'retry_policy' => $this->retryPolicy,
            'result' => $this->result,
            'error' => $this->error ? [
                'class' => get_class($this->error),
                'message' => $this->error->getMessage(),
                'code' => $this->error->getCode(),
                'file' => $this->error->getFile(),
                'line' => $this->error->getLine(),
            ] : null,
        ]);
    }
}
