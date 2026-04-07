<?php

namespace Tests;

use Examples\Greeter\Greet;
use Tsqm\Errors\RootHasBeenDeleted;
use Tsqm\Task;

class ConcurrencyTest extends TestCase
{
    public function testRootConcurrentDeletion(): void
    {
        $greet = $this->container->get(Greet::class);
        $task = (new Task())->setCallable($greet)->setArgs('John Doe');

        // process A starts and finishes task
        $ptask = $this->tsqm->run($task, true);
        $this->assertTrue($this->repository->isTaskExists($ptask->getRootId()));
        $this->assertFalse($ptask->isFinished());

        $this->tsqm->run($ptask);
        $this->assertFalse($this->repository->isTaskExists($ptask->getRootId()));

        // process B
        $this->expectException(RootHasBeenDeleted::class);
        $this->tsqm->run($ptask);
    }
}
