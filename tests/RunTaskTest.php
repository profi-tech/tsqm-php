<?php

namespace Tests;

use Examples\Greeter\Greeter;
use Examples\Greeter\Greeting;
use Examples\Greeter\GreeterError;
use Tsqm\Runs\RunOptions;
use Tsqm\TsqmTasks;
use Tsqm\Tasks\Task;
use Tsqm\Tasks\TaskRetryPolicy;

class RunTaskTest extends TestCase
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

    public function testTaskSuccess()
    {
        /** @var Task */
        $task = $this->greeterTasks->simpleGreet('John Doe');
        $run = $this->tsqm->createRun(
            (new RunOptions)
                ->setTask($task)
        );
        $result = $this->tsqm->performRun($run);

        $this->assertTrue($result->isReady());
        $this->assertFalse($result->hasError());
        $this->assertEquals((new Greeting("Hello, John Doe!"))->setSent(true), $result->getData());
    }

    public function testTaskFail()
    {
        /** @var Task */
        $task = $this->greeterTasks->simpleGreetWith3Fails('John Doe');
        $run = $this->tsqm->createRun(
            (new RunOptions)
                ->setTask($task)
        );

        $this->expectException(GreeterError::class);
        $this->expectExceptionCode(1700403919);
        $this->expectExceptionMessage("Greet failed");

        $this->tsqm->performRun($run);
    }

    public function testTaskFailRetrySuccess()
    {
        /** @var Task */
        $task = $this->greeterTasks->simpleGreetWith3Fails('John Doe');
        $task->setRetryPolicy((new TaskRetryPolicy)->setMaxRetries(3));
        $run = $this->tsqm->createRun(
            (new RunOptions)
                ->setTask($task)
        );

        for ($i = 1; $i <= 3; $i++) {
            $result = $this->tsqm->performRun($run);
            $this->assertFalse($result->isReady(), "Step #$i");
        }

        $result = $this->tsqm->performRun($run);
        $this->assertTrue($result->isReady());
        $this->assertFalse($result->hasError());
        $this->assertEquals((new Greeting("Hello, John Doe!"))->setSent(true), $result->getData());
    }

    public function testTaskFailRetryFail()
    {
        /** @var Task */
        $task = $this->greeterTasks->simpleGreetWith3Fails('John Doe');
        $task->setRetryPolicy((new TaskRetryPolicy)->setMaxRetries(2));
        $run = $this->tsqm->createRun(
            (new RunOptions)
                ->setTask($task)
        );

        for ($i = 1; $i <= 2; $i++) {
            $result = $this->tsqm->performRun($run);
            $this->assertFalse($result->isReady(), "Step #$i");
        }

        $this->expectException(GreeterError::class);
        $this->expectExceptionCode(1700403919);
        $this->expectExceptionMessage("Greet failed");
        $this->tsqm->performRun($run);
    }
}
