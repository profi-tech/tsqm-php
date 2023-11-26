<?php

namespace Tsqm\Events;

interface EventRepositoryInterface
{
    public function addEvent(string $runId, string $type, string $taskId, $payload = null, ?string $salt = null);

    public function getStartedEvents(string $runId): array;

    public function getCompletionEvent(string $runId, string $taskId): ?Event;

    public function getFailedEvents(string $runId, string $taskId);
}
