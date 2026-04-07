<?php

namespace Tests;

use Examples\Greeter\RecursiveGreet;
use Tsqm\Options;
use Tsqm\RetryPolicy;
use Tsqm\Task;
use Tsqm\Tsqm;

class TaskLoadTest extends TestCase
{
    public function testRecursiveDeepGenerator(): void
    {
        $tsqm = new Tsqm(
            (new Options())
                ->setRepository($this->repository)
                ->setContainer($this->container)
                ->setMaxNestingLevel(1001)
                ->setForceSyncRuns(true)
        );

        $recusiveGreet = $this->container->get(RecursiveGreet::class);
        $task = (new Task())
            ->setCallable($recusiveGreet)
            ->setRetryPolicy(
                (new RetryPolicy())->setMaxRetries(2)
            )
            ->setArgs('John Doe', 1000, true);

        $task = $tsqm->run($task);
        $this->assertFalse($task->isFinished());

        $task = $tsqm->run($task);
        $this->assertFalse($task->isFinished());

        $task = $tsqm->run($task);
        $this->assertTrue($task->isFinished());
    }
}
