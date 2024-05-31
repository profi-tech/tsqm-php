<?php

namespace Tsqm\Events;

interface EventRepositoryInterface
{
    /**
     * @param mixed $payload
     */
    public function addEvent(
        string $runId,
        string $type,
        string $taskId,
        $payload = null,
        ?string $salt = null
    ): Event;

    /**
     * @return Event[]
     */
    public function getStartedEvents(string $runId): array;

    public function getCompletionEvent(string $runId, string $taskId): ?Event;

    /**
     * @return Event[]
     */
    public function getFailedEvents(string $runId, string $taskId): array;
}
