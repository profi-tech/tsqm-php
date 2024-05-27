<?php

namespace Tsqm\Runs;

use DateTime;
use Tsqm\Errors\InvalidRetryPolicy;
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
    private ?RunRetryPolicy $retryPolicy;
    private string $status;

    public function __construct(
        string $id,
        DateTime $createdAt,
        DateTime $scheduledFor,
        Task $task,
        ?RunRetryPolicy $retryPolicy,
        string $status
    ) {
        $this->id = $id;
        $this->createdAt = $createdAt;
        $this->scheduledFor = $scheduledFor;
        $this->task = $task;
        $this->retryPolicy = $retryPolicy;
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

    public function getRetryPolicy(): ?RunRetryPolicy
    {
        return $this->retryPolicy;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public static function fromArray(array $data): Run
    {
        $task = SerializationHelper::unserialize($data['task']);
        $retryPolicy = null;

        if ($data['retry_policy']) {
            $retryPolicyArray = json_decode($data['retry_policy'], true);
            if (!is_array($retryPolicyArray)) {
                throw new InvalidRetryPolicy("Failed to unserialize retry policy");
            }                
            $retryPolicy = RunRetryPolicy::fromArray($retryPolicyArray);
        }
        
        return new Run(
            $data['id'],
            new DateTime($data['created_at']),
            $data['scheduled_for'] ? new DateTime($data['scheduled_for']) : null,
            $task,
            $retryPolicy,
            $data['status'],
        );
    }
}
