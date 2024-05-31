<?php

namespace Tests;

use Examples\Greeter\Greeting;
use Tsqm\Tasks\Task;
use Examples\Greeter\GreeterError;

class RunResultTest extends TestCase
{
    public function testSuccessfulRunResult()
    {
        $task = (new Task($this->simpleGreet))->setArgs('John Doe');
        $run = $this->tsqm->createRun($task);
        $result = $this->tsqm->performRun($run);

        $this->assertEquals($run->getId(), $result->getRunId());
        $this->assertTrue($result->isReady());
        $this->assertFalse($result->hasError());
        $this->assertEquals((new Greeting("Hello, John Doe!"))->setSent(true), $result->getData());
    }

    public function testTaskSuccessSecondRun()
    {
        $task = (new Task($this->simpleGreet))->setArgs('John Doe');
        $run = $this->tsqm->createRun($task);
        $result = $this->tsqm->performRun($run);

        $this->assertTrue($result->isReady());
        $this->assertFalse($result->hasError());
        $this->assertEquals((new Greeting("Hello, John Doe!"))->setSent(true), $result->getData());

        $result = $this->tsqm->performRun($run);
        $this->assertTrue($result->isReady());
        $this->assertFalse($result->hasError());
        $this->assertEquals((new Greeting("Hello, John Doe!"))->setSent(true), $result->getData());
    }

    public function testTaskFailSecondRun()
    {
        $task = (new Task($this->simpleGreetWith3Fails))->setArgs('John Doe');
        $run = $this->tsqm->createRun($task);

        $this->expectException(GreeterError::class);
        $this->expectExceptionCode(1700403919);
        $this->expectExceptionMessage("Greet failed");

        $this->tsqm->performRun($run);

        $result = $this->tsqm->performRun($run);
        $this->assertTrue($result->isReady());
        $this->assertTrue($result->hasError());
        $this->assertEquals("Greet failed", $result->getError()->getMessage());
        $this->assertEquals(1700403919, $result->getError()->getCode());
    }
}
