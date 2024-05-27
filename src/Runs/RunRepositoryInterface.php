<?php

namespace Tsqm\Runs;

use DateTime;

interface RunRepositoryInterface
{
    public function createRun(Run $run);

    public function getRun(string $runId): ?Run;

    public function updateRunStatus(string $runId, string $status);

    public function updateRunScheduledFor(string $runId, DateTime $scheduledFor);

    public function getScheduledRunIds(DateTime $until, int $limit): array;
}
