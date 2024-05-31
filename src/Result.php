<?php

namespace Tsqm;

use Error;
use Tsqm\Events\Event;

class Result
{
    private string $runId;
    private ?Event $event;

    public function __construct(string $runId, ?Event $event)
    {
        $this->runId = $runId;
        $this->event = $event;
    }

    public function getRunId(): string
    {
        return $this->runId;
    }

    public function isReady()
    {
        return $this->event && in_array($this->event->getType(), [
            Event::TYPE_TASK_COMPLETED,
            Event::TYPE_TASK_CRASHED,
        ]);
    }

    public function hasData()
    {
        return $this->event && $this->event->getPayload() !== null;
    }

    public function getData()
    {
        return $this->event->getPayload();
    }

    public function hasError()
    {
        return $this->event && ($this->event->getPayload() instanceof Error);
    }

    public function getError(): ?Error
    {
        if ($this->hasError()) {
            return $this->event->getPayload();
        } else {
            return null;
        }
    }
}
