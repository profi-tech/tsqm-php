<?php

namespace Tsqm\Runs;

use DateTime;
use Tsqm\Tasks\Task;

interface RunRepositoryInterface
{
    public function createRun(Task $task): Run;

    public function getRun(string $runId): Run;

    public function updateRunStatus(string $runId, string $status);

    public function updateRunAt(string $runId, DateTime $runAt): Run;

    public function getNextRunIds(DateTime $until, int $limit): array;
}
