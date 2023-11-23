<?php

namespace Tests;

use Tsqm\TsqmTasks;
use Tsqm\Tasks\Task;
use Examples\Greeter\Greeter;


class RunStatusTest extends TestCase
{
    /** @var Greeter */
    private $greeter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->greeter = new TsqmTasks(
            $this->container->get(Greeter::class)
        );
    }

    public function testTaskSucceed()
    {
        /** @var Task */
        $task = $this->greeter->simpleGreet('John Doe');
        $run = $this->tsqm->createRun($task);
        $result = $this->tsqm->performRun($run);
        $run = $this->tsqm->getRun($run->getId());

        $this->assertEquals('finished', $run->getStatus());
        $this->assertTrue($result->isReady());
    }

    public function testTaskFails()
    {
        /** @var Task */
        $task = $this->greeter->simpleGreetWith3Fails('John Doe');
        $run = $this->tsqm->createRun($task);
        $result = $this->tsqm->performRun($run);
        $run = $this->tsqm->getRun($run->getId());

        $this->assertEquals('started', $run->getStatus());
        $this->assertFalse($result->isReady());
    }

    public function testAsyncRun()
    {
        /** @var Task */
        $task = $this->greeter->simpleGreet('John Doe');
        $run = $this->tsqm->createRun($task);
        $result = $this->tsqm->performRun($run, true);
        $run = $this->tsqm->getRun($run->getId());

        $this->assertEquals('created', $run->getStatus());
        $this->assertFalse($result->isReady());
    }

    public function testScheduledRun()
    {
        /** @var Task */
        $task = $this->greeter->simpleGreet('John Doe');
        $run = $this->tsqm->createRun($task, (new \DateTime())->modify('+1 day'));
        $result = $this->tsqm->performRun($run);
        $run = $this->tsqm->getRun($run->getId());

        $this->assertEquals('created', $run->getStatus());
        $this->assertFalse($result->isReady());
    }
}
