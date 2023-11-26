<?php

namespace Tsqm\Events;

use DateTime;
use Tsqm\Helpers\SerializationHelper;

class Event
{
    const TYPE_TASK_STARTED = 'taskStarted';
    const TYPE_TASK_FAILED = 'taskFailed';
    const TYPE_TASK_COMPLETED = 'taskCompleted';
    const TYPE_TASK_CRASHED = 'taskCrashed';

    private ?int $id;
    private string $runId;
    private DateTime $ts;
    private string $type;
    private string $taskId;
    private $payload;

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

    public function getRunId()
    {
        return $this->runId;
    }

    public function getTs()
    {
        return $this->ts;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getTaskId()
    {
        return $this->taskId;
    }

    public function getPayload()
    {
        return $this->payload;
    }

    public function getHash(?string $salt = null)
    {
        return md5(implode("::", [
            $this->runId,
            $this->type,
            $this->taskId,
            $salt,
        ]));
    }

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
