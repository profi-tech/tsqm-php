<?php

namespace Tests;

use Examples\Greeter\Greeting;
use Examples\Greeter\Greeter;
use Tsqm\TsqmTasks;
use Tsqm\Tasks\Task;
use Examples\Greeter\GreeterError;
use Tsqm\Tasks\TaskRetryPolicy;

class RunResultTest extends TestCase
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

    public function testSuccessfulRunResult()
    {
        /** @var Task */
        $task = $this->greeterTasks->simpleGreet('John Doe');
        $run = $this->tsqm->createRun($task);
        $result = $this->tsqm->performRun($run);

        $this->assertEquals($run->getId(), $result->getRunId());
        $this->assertTrue($result->isReady());
        $this->assertFalse($result->hasError());
        $this->assertEquals((new Greeting("Hello, John Doe!"))->setSent(true), $result->getData());
    }

    public function testTaskSuccessSecondRun()
    {
        /** @var Task */
        $task = $this->greeterTasks->simpleGreet('John Doe');
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
        /** @var Task */
        $task = $this->greeterTasks->simpleGreetWith3Fails('John Doe');
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
