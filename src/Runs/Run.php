<?php

namespace Tsqm\Runs;

use DateTime;
use Tsqm\Tasks\Task;
use Tsqm\Helpers\SerializationHelper;

class Run
{
    const STATUS_CREATED = 'created';
    const STATUS_STARTED = 'started';
    const STATUS_FINISHED = 'finished';

    private string $id;
    private DateTime $createdAt;
    private DateTime $scheduledFor;
    private Task $task;
    private string $status;

    public function __construct(
        string $id,
        DateTime $createdAt,
        DateTime $scheduledFor,
        Task $task,
        string $status
    ) {
        $this->id = $id;
        $this->createdAt = $createdAt;
        $this->scheduledFor = $scheduledFor;
        $this->task = $task;
        $this->status = $status;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function getScheduledFor(): DateTime
    {
        return $this->scheduledFor;
    }

    public function getTask(): Task
    {
        return $this->task;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public static function fromArray(array $data): Run
    {
        $task = SerializationHelper::unserialize($data['task']);
        return new Run(
            $data['id'],
            new DateTime($data['created_at']),
            $data['scheduled_for'] ? new DateTime($data['scheduled_for']) : null,
            $task,
            $data['status'],
        );
    }
}
