<?php
namespace Tsqm\Runs;

use Tsqm\Tasks\Task;
use DateTime;

class RunOptions {

    private ?Task $task = null;
    private ?DateTime $createdAt = null;
    private ?DateTime $scheduledFor = null;

    public function setTask(Task $task): self {
        $this->task = $task;
        return $this;
    }

    public function getTask(): ?Task {
        return $this->task;
    }

    public function setCreatedAt(DateTime $createdAt): self {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getCreatedAt(): ?DateTime {
        return $this->createdAt;
    }

    public function setScheduledFor(DateTime $scheduledFor): self {
        $this->scheduledFor = $scheduledFor;
        return $this;
    }

    public function getScheduledFor(): ?DateTime {
        return $this->scheduledFor;
    }
}