<?php

namespace Tests;

use Examples\Greeter\RecursiveGreet;
use Examples\TsqmContainer;
use Tsqm\Options;
use Tsqm\RetryPolicy;
use Tsqm\Task;
use Tsqm\Tsqm;

class TaskLoadTest extends TestCase
{
    public function testRecursiveDeepGenerator(): void
    {
        $tsqm = new Tsqm(
            $this->pdo,
            (new Options())
                ->setContainer(new TsqmContainer($this->psrContainer))
                ->setMaxNestingLevel(1001)
                ->setForceSyncRuns(true)
        );

        $recusiveGreet = $this->psrContainer->get(RecursiveGreet::class);
        $task = (new Task())
            ->setCallable($recusiveGreet)
            ->setRetryPolicy(
                (new RetryPolicy())->setMaxRetries(2)
            )
            ->setArgs('John Doe', 1000, true);

        $task = $tsqm->runTask($task);
        $this->assertFalse($task->isFinished());

        $task = $tsqm->runTask($task);
        $this->assertFalse($task->isFinished());

        $task = $tsqm->runTask($task);
        $this->assertTrue($task->isFinished());
    }
}
