<?php

namespace Tests;

use DateTime;
use Examples\Greeter\Greeter;
use Tsqm\Tasks\TaskDecorator;
use Tsqm\Tasks\Task;

class RunCreateTest extends TestCase
{
    /** @var Greeter */
    private $greeter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->greeter = new TaskDecorator(
            $this->container->get(Greeter::class)
        );
    }

    public function testRunID()
    {
        /** @var Task */
        $task = $this->greeter->simpleGreet('John Doe');
        $run = $this->tsqm->createRun($task);
        $this->assertTrue(preg_match('/^[a-f0-9]{8}-([a-f0-9]{4}-){3}[a-f0-9]{12}$/', $run->getId()) === 1);
    }

    public function testTaskId()
    {
        /** @var Task */
        $task = $this->greeter->simpleGreet('John Doe');
        $run = $this->tsqm->createRun($task);

        $this->assertEquals($run->getTask()->getId(), $task->getId());
    }

    public function testCreatedAt()
    {
        /** @var Task */
        $task = $this->greeter->simpleGreet('John Doe');
        $run = $this->tsqm->createRun($task);

        $this->assertTrue(
            $this->assertHelper->isDateTimeEqualsWithDelta($run->getCreatedAt(), new DateTime(), 10)
        );
    }

    public function testStatus()
    {
        /** @var Task */
        $task = $this->greeter->simpleGreet('John Doe');
        $run = $this->tsqm->createRun($task);

        $this->assertEquals($run->getStatus(), 'created');
    }
}
