<?php

namespace Tsqm\Events;

use DateTime;
use Tsqm\Helpers\SerializationHelper;

class Event
{
    public const TYPE_TASK_STARTED = 'taskStarted';
    public const TYPE_TASK_FAILED = 'taskFailed';
    public const TYPE_TASK_COMPLETED = 'taskCompleted';
    public const TYPE_TASK_CRASHED = 'taskCrashed';

    private ?int $id;
    private string $runId;
    private DateTime $ts;
    private string $type;
    private string $taskId;

    /** @var mixed */
    private $payload;

    /**
     * @param mixed $payload
     */
    public function __construct(?int $id, string $runId, DateTime $ts, string $type, string $taskId, $payload)
    {
        $this->id = $id;
        $this->runId = $runId;
        $this->ts = $ts;
        $this->type = $type;
        $this->taskId = $taskId;
        $this->payload = $payload;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function withId(int $id): self
    {
        $cloned = clone $this;
        $cloned->id = $id;
        return $cloned;
    }

    public function getRunId(): string
    {
        return $this->runId;
    }

    public function getTs(): DateTime
    {
        return $this->ts;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getTaskId(): string
    {
        return $this->taskId;
    }

    /**
     * @return mixed
     */
    public function getPayload()
    {
        return $this->payload;
    }

    public function getHash(?string $salt = null): string
    {
        return md5(implode("::", [
            $this->runId,
            $this->type,
            $this->taskId,
            $salt,
        ]));
    }

    /**
     * @param array<string, mixed> $data
     * @return Event
     */
    public static function fromArray(array $data): Event
    {
        $payload = SerializationHelper::unserialize($data['payload']);
        return new self(
            $data['id'],
            $data['run_id'],
            new DateTime($data['ts']),
            $data['type'],
            $data['task_id'],
            $payload,
        );
    }
}
