<?php

namespace Tsqm;

use DateTime;

class Run
{
    const STATUS_CREATED = 'created';
    const STATUS_STARTED = 'started';
    const STATUS_FINISHED = 'finished';

    private string $id;
    private DateTime $createdAt;
    private Task $task;
    private string $status;

    public function __construct(
        string $id,
        DateTime $createdAt,
        Task $task,
        string $status
    ) {
        $this->id = $id;
        $this->createdAt = $createdAt;
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
        return new Run(
            $data['id'],
            new DateTime($data['created_at']),
            unserialize($data['task']),
            $data['status'],
        );
    }
}
