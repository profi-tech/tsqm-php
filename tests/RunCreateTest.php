<?php

namespace Tests;

use DateTime;
use Tsqm\Tasks\Task;

class RunCreateTest extends TestCase
{
    public function testRunID()
    {
        $task = (new Task($this->simpleGreet))->setArgs('John Doe');
        $run = $this->tsqm->createRun($task);
        $this->assertTrue(preg_match('/^[a-f0-9]{8}-([a-f0-9]{4}-){3}[a-f0-9]{12}$/', $run->getId()) === 1);
    }

    public function testTaskId()
    {
        $task = (new Task($this->simpleGreet))->setArgs('John Doe');
        $run = $this->tsqm->createRun($task);
        $this->assertEquals($run->getTask()->getId(), $task->getId());
    }

    public function testCreatedAt()
    {
        $task = (new Task($this->simpleGreet))->setArgs('John Doe');
        $run = $this->tsqm->createRun($task);
        $this->assertTrue(
            $this->assertHelper->isDateTimeEqualsWithDelta($run->getCreatedAt(), new DateTime(), 10)
        );
    }

    public function testStatus()
    {
        $task = (new Task($this->simpleGreet))->setArgs('John Doe');
        $run = $this->tsqm->createRun($task);
        $this->assertEquals($run->getStatus(), 'created');
    }

}
