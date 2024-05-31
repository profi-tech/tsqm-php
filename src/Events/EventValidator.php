<?php

namespace Tsqm\Events;

use Tsqm\Errors\EventTypeMismatch;
use Tsqm\Errors\TaskIdMismatch;

class EventValidator
{
    public function validateEventType(Event $event, array $expectedTypes)
    {
        if (!in_array($event->getType(), $expectedTypes)) {
            throw new EventTypeMismatch(
                "Event #{$event->getId()} type mismatch: got {$event->getType()}, but want one of [" . implode(",", $expectedTypes) . "]"
            );
        }
    }

    public function validateEventTaskId(Event $event, string $expectedTaskId)
    {
        if ($event->getTaskId() != $expectedTaskId) {
            throw new TaskIdMismatch(
                "Event #{$event->getId()} taskId mismatch: got {$event->getTaskId()}, but want {$expectedTaskId}"
            );
        }
    }
}
