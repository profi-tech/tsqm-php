<?php

namespace Tsqm\Runs;

use DateTime;
use Exception;
use Tsqm\Errors\EnqueueFailed;
use Tsqm\Runs\Queue\RunQueueInterface;

class RunScheduler implements RunSchedulerInterface
{
    private RunRepositoryInterface $runRepository;
    private RunQueueInterface $queue;

    public function __construct(RunRepositoryInterface $runRepository, RunQueueInterface $queue)
    {
        $this->runRepository = $runRepository;
        $this->queue = $queue;
    }

    public function scheduleRun(Run $run, DateTime $scheduleFor)
    {
        if ($run->getScheduledFor() != $scheduleFor) {
            $this->runRepository->updateRunScheduledFor($run->getId(), $scheduleFor);
            $run = $this->runRepository->getRun($run->getId());
        }
        try {
            $this->queue->enqueueRun($run);
        } catch (Exception $e) {
            throw new EnqueueFailed("Enqueue failed: " . $e->getMessage(), null, $e);
        }
    }
}
