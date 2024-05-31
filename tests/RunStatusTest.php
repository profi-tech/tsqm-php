<?php

namespace Tests;

use Tsqm\Tasks\Task;
use Tsqm\Tasks\RetryPolicy;

class RunStatusTest extends TestCase
{
    public function testTaskSucceed()
    {
        $task = (new Task($this->simpleGreet))->setArgs('John Doe');
        $run = $this->tsqm->createRun($task);
        $result = $this->tsqm->performRun($run);
        $run = $this->tsqm->getRun($run->getId());

        $this->assertEquals('finished', $run->getStatus());
        $this->assertTrue($result->isReady());
    }

    public function testTaskFaildAndScheduled()
    {
        $task = (new Task($this->simpleGreetWith3Fails))
            ->setArgs('John Doe')
            ->setRetryPolicy(
                (new RetryPolicy())
                    ->setMaxRetries(1)
            );
        $run = $this->tsqm->createRun($task);
        $result = $this->tsqm->performRun($run);
        $run = $this->tsqm->getRun($run->getId());

        $this->assertEquals('started', $run->getStatus());
        $this->assertFalse($result->isReady());
    }

    public function testAsyncRun()
    {
        $task = (new Task($this->simpleGreet))->setArgs('John Doe');
        $run = $this->tsqm->createRun($task);
        $result = $this->tsqm->performRun($run, true);
        $run = $this->tsqm->getRun($run->getId());

        $this->assertEquals('created', $run->getStatus());
        $this->assertFalse($result->isReady());
    }

    public function testScheduledRun()
    {
        $task = (new Task($this->simpleGreet))
            ->setArgs('John Doe')
            ->setScheduledFor((new \DateTime())->modify('+1 day'));
        $run = $this->tsqm->createRun($task);
        $result = $this->tsqm->performRun($run);
        $run = $this->tsqm->getRun($run->getId());

        $this->assertEquals('created', $run->getStatus());
        $this->assertFalse($result->isReady());
    }
}
