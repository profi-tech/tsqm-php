<?php

namespace Tsqm\Runs;

use DateTime;
use Tsqm\Tasks\Task;

interface RunRepositoryInterface
{
    public function createRun(Task $task, DateTime $createdAt, DateTime $scheduledFor): Run;

    public function getRun(string $runId): ?Run;

    public function updateRunStatus(string $runId, string $status);

    public function updateRunScheduledFor(string $runId, DateTime $scheduledFor);

    public function getScheduledRunIds(DateTime $until, int $limit): array;
}
