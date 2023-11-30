<?php

namespace Tests;

use Tsqm\TsqmTasks;
use Tsqm\Tasks\Task;
use Examples\Greeter\Greeter;
use Tsqm\Runs\RunOptions;

class RunStatusTest extends TestCase
{
    /** @var Greeter */
    private $greeterTasks;

    protected function setUp(): void
    {
        parent::setUp();
        $this->greeterTasks = new TsqmTasks(
            $this->container->get(Greeter::class)
        );
    }

    public function testTaskSucceed()
    {
        /** @var Task */
        $task = $this->greeterTasks->simpleGreet('John Doe');
        $run = $this->tsqm->createRun($task);
        $result = $this->tsqm->performRun($run);
        $run = $this->tsqm->getRun($run->getId());

        $this->assertEquals('finished', $run->getStatus());
        $this->assertTrue($result->isReady());
    }

    public function testTaskFaildAndScheduled()
    {
        /** @var Task */
        $task = $this->greeterTasks->simpleGreetWith3Fails('John Doe');
        $run = $this->tsqm->createRun($task->setRetryPolicy(
            $task->getRetryPolicy()->setMaxRetries(1)
        ));
        $result = $this->tsqm->performRun($run);
        $run = $this->tsqm->getRun($run->getId());

        $this->assertEquals('started', $run->getStatus());
        $this->assertFalse($result->isReady());
    }

    public function testAsyncRun()
    {
        /** @var Task */
        $task = $this->greeterTasks->simpleGreet('John Doe');
        $run = $this->tsqm->createRun($task);
        $result = $this->tsqm->performRun($run, (new RunOptions)->setForceAsync(true));
        $run = $this->tsqm->getRun($run->getId());

        $this->assertEquals('created', $run->getStatus());
        $this->assertFalse($result->isReady());
    }

    public function testScheduledRun()
    {
        /** @var Task */
        $task = $this->greeterTasks->simpleGreet('John Doe');
        $run = $this->tsqm->createRun($task, (new \DateTime())->modify('+1 day'));
        $result = $this->tsqm->performRun($run);
        $run = $this->tsqm->getRun($run->getId());

        $this->assertEquals('created', $run->getStatus());
        $this->assertFalse($result->isReady());
    }
}
